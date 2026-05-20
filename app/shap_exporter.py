import pickle
from pathlib import Path
import re 
import spacy

import numpy as np
import shap
from bs4 import BeautifulSoup
from bs4.element import NavigableString, Tag
import unicodedata

from logger import log


class RoRoShapWorkerExporter:
    def __init__(
        self,
        pickle_path,
        tokenizer=r"\W+",
        output_names=None,
        detail_level = 50,
        min_k=8,
        max_k=None,
        shap_injection=None,
        text_variant="cleaned",
        spacy_model_name="ro_core_news_lg",
    ):
        self.pickle_path = Path(pickle_path)

        self.tokenizer = tokenizer
        self.output_names = output_names
        self.shap_injection = shap_injection

        self.text_variant = text_variant
        self.spacy_model_name = spacy_model_name
        self._spacy_model = None

        self.detail_level = max(1, min(100, int(detail_level)))
        self.min_k = min_k
        self.max_k = max_k

        self.payload = None
        self.model = None
        self.vectorizer = None
        self.explainer = None

      

        self.load()

    def load(self):
        with self.pickle_path.open("rb") as f:
            self.payload = pickle.load(f)

        self.model = self.payload.get("model")
        self.vectorizer = self.payload.get("vectorizer")

        if self.model is None:
            raise ValueError("Pickle does not contain model")

        if self.vectorizer is None:
            raise ValueError("Pickle does not contain vectorizer")

        if self.output_names is None:
            self.output_names = self.payload.get("labels")

        self.explainer = shap.Explainer(
            self.predict_proba_text,
            shap.maskers.Text(tokenizer=self.tokenizer),
            output_names=self.output_names,
        )

        return self

    def predict_proba_text(self, texts):
        X = self.vectorizer.transform(texts)
        return self.model.predict_proba(X)

    def export_one(self, text):
        raw_text = text 
        text = self.preprocess_text(text)

        shap_values = self.explainer([text])

        sv = shap_values[0]

        k = self._get_k(text)

        sv = self.keep_topk_tokens(sv, k)

        html = shap.plots.text(sv, display=False)

        if self.shap_injection == "spaces":
            html = self.inject_text_between_token_divs(html, raw_text)

        elif self.shap_injection == "placeholder":
            html = self.inject_text_by_percent_tags(html, raw_text, log=None)

        pred = self.model.predict(
            self.vectorizer.transform([text])
        )[0]

        return (
            f"<h3>SHAP explanation</h3>"
            f"<p><b>Predicted:</b> {pred}</p>"
            f"{html}"
        )

    def _get_k(self, text):
        wc = len(text.split())

        if self.detail_level >= 100:
            return wc

        # maps 1..100 to roughly 5%..100%
        ratio = 0.05 + (self.detail_level / 100) * 0.95

        k = int(wc * ratio)

        k = max(self.min_k, k)

        if self.max_k is not None:
            k = min(self.max_k, k)

        return min(k, wc)

    @staticmethod
    def keep_topk_tokens(sv, k=20):
        vals = np.array(sv.values, copy=True)

        if vals.ndim == 2:
            score = np.max(np.abs(vals), axis=1)
        else:
            score = np.abs(vals)

        if k < len(score):
            keep_idx = np.argsort(score)[-k:]

            mask = np.zeros(len(score), dtype=bool)
            mask[keep_idx] = True
        else:
            mask = np.ones(len(score), dtype=bool)

        if vals.ndim == 2:
            vals[~mask, :] = 0
        else:
            vals[~mask] = 0

        return shap.Explanation(
            values=vals,
            base_values=sv.base_values,
            data=sv.data,
            output_names=getattr(sv, "output_names", None),
            clustering=getattr(sv, "clustering", None),
        )
    
    def preprocess_text(self, text):
        if self.text_variant in {"cleaned", "raw", None}:
            return text

        if self.text_variant == "ner":
            return self._remove_ner(text, use_placeholder=False)

        if self.text_variant == "ner-ph":
            return self._remove_ner(text, use_placeholder=True)

        if self.text_variant == "stop":
            return self._keep_stopwords(text, use_placeholder=False)

        if self.text_variant == "stop-ph":
            return self._keep_stopwords(text, use_placeholder=True)

        raise ValueError(f"Unknown text_variant: {self.text_variant}")


    def _get_spacy_model(self):
        if self._spacy_model is None:
            self._spacy_model = spacy.load(
                self.spacy_model_name,
                disable=["lemmatizer", "textcat"]
            )

        return self._spacy_model


    def _remove_ner(self, text, use_placeholder=False):
        doc = self._get_spacy_model()(text)

        result = []
        i = 0

        while i < len(doc):
            token = doc[i]

            if token.ent_iob_ == "B":
                ent_type = token.ent_type_

                j = i + 1
                while j < len(doc) and doc[j].ent_iob_ == "I":
                    j += 1

                if use_placeholder:
                    result.append(f"%{ent_type}% ")

                i = j
            else:
                if token.ent_iob_ == "O":
                    result.append(token.text_with_ws)

                i += 1

        return "".join(result).strip()


    def _keep_stopwords(self, text, use_placeholder=False):
        doc = self._get_spacy_model()(text)

        return "".join(
            t.text_with_ws
            if t.is_stop or t.is_punct or t.is_space
            else (f"%{t.pos_}% " if use_placeholder else "")
            for t in doc
        ).strip()
    
    @staticmethod
    def _norm_with_map(s: str):
        norm = []
        pos_map = []

        for i, ch in enumerate(s):
            if re.match(r"[\wăâîșțĂÂÎȘȚ]", ch, flags=re.UNICODE):
                norm.append(ch.lower())
                pos_map.append(i)

        return "".join(norm), pos_map


    @staticmethod
    def _raw_parts_for_alignment(full_text: str):
        parts = []

        for part in re.findall(r"\S+\s*", full_text, flags=re.UNICODE):
            # split hyphenated words only
            m = re.match(r"^([\wăâîșțĂÂÎȘȚ]+)-([\wăâîșțĂÂÎȘȚ]+)([^\wăâîșțĂÂÎȘȚ\s]*\s*)$", part, flags=re.UNICODE)

            if m:
                first, second, tail = m.groups()
                parts.append(first + "-")
                parts.append(second + tail)
            else:
                parts.append(part)

        return parts


    @staticmethod
    def inject_text_between_token_divs(html: str, full_text: str) -> str:
        soup = BeautifulSoup(html, "html.parser")

        token_id_rx = re.compile(r"^_tp_.*_ind_\d+$")

        containers = [
            lab.parent
            for lab in soup.find_all("div")
            if isinstance(lab, Tag)
            and lab.get_text(strip=True) == "inputs"
            and isinstance(lab.parent, Tag)
        ]

        for cont in containers:
            raw_parts = []

            for part in RoRoShapWorkerExporter._raw_parts_for_alignment(full_text):
                norm, pos_map = RoRoShapWorkerExporter._norm_with_map(part)

                raw_parts.append({
                    "text": part,
                    "norm": norm,
                    "map": pos_map,
                })

            token_divs = [
                d for d in cont.find_all("div")
                if isinstance(d, Tag)
                and d.get("id")
                and token_id_rx.match(d["id"])
            ]

            raw_i = 0

            for d in token_divs:
                token_text = d.get_text(strip=False)
                token_norm, _ = RoRoShapWorkerExporter._norm_with_map(token_text)

                if not token_norm:
                    continue

                inserted_gap = []
                matched = False

                while raw_i < len(raw_parts):
                    raw = raw_parts[raw_i]

                    if raw["norm"] == token_norm:
                        if inserted_gap:
                            d.insert_before(NavigableString("".join(inserted_gap)))

                        d.clear()
                        d.append(NavigableString(raw["text"]))

                        raw_i += 1
                        matched = True
                        break

                    inserted_gap.append(raw["text"])
                    raw_i += 1

                if not matched:
                    log(f"[warn] could not align token: {token_text!r}")

            if raw_i < len(raw_parts) and token_divs:
                tail = "".join(raw["text"] for raw in raw_parts[raw_i:])
                token_divs[-1].insert_after(NavigableString(tail))

        return str(soup)

    @staticmethod
    def inject_text_by_percent_tags(html: str, full_text: str, *, log=True) -> str:
        soup = BeautifulSoup(html, "html.parser")

        token_id_rx = re.compile(r"^_tp_.*_ind_\d+$")
        tag_tail_rx = re.compile(r"^[A-Z]+%")

        containers = [
            lab.parent
            for lab in soup.find_all("div")
            if isinstance(lab, Tag)
            and lab.get_text(strip=True) == "inputs"
            and isinstance(lab.parent, Tag)
        ]

        for cont in containers:
            raw_words = re.findall(r"\S+", full_text)
            raw_i = 0

            token_divs = [
                d for d in cont.find_all("div")
                if isinstance(d, Tag)
                and d.get("id")
                and token_id_rx.match(d["id"])
            ]

            for d in token_divs:
                text = d.get_text(strip=False)

                def repl_tag(match):
                    nonlocal raw_i

                    if raw_i >= len(raw_words):
                        return ""

                    word = raw_words[raw_i]
                    raw_i += 1

                    return word

                new_text = tag_tail_rx.sub(repl_tag, text, count=1)
                new_text = new_text.replace("%", "")

                visible_words = re.findall(r"\S+", new_text)

                for w in visible_words:
                    if raw_i < len(raw_words) and w.strip() == raw_words[raw_i]:
                        raw_i += 1

                d.clear()
                d.append(NavigableString(new_text))

        return str(soup)

    
    @staticmethod
    def _norm_align_token(s: str) -> str:
        s = s.lower().strip()
        s = re.sub(r"[^\wăâîșțĂÂÎȘȚ]", "", s, flags=re.UNICODE)
        return s
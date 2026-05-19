import pickle
from pathlib import Path

import numpy as np
import shap


class RoRoShapWorkerExporter:
    def __init__(
        self,
        pickle_path,
        tokenizer=r"\W+",
        output_names=None,
        detail_level = 50,
        min_k=8,
        max_k=None,
    ):
        self.pickle_path = Path(pickle_path)

        self.tokenizer = tokenizer
        self.output_names = output_names

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
        shap_values = self.explainer([text])

        sv = shap_values[0]

        k = self._get_k(text)

        sv = self.keep_topk_tokens(sv, k)

        html = shap.plots.text(sv, display=False)

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
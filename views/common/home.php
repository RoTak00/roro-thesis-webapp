<?= $head ?>

<div class="layout">
    <div>
        <h1>Detectie Ro / Md</h1>

        <form id="form">
            <div class="field">
                <label for="task">Task</label>

                <select name="task" id="task">
                    <option value="romd" selected>Ro / Md</option>
                    <option value="regions">Regions</option>
                </select>
            </div>

            <div class="field">
                <label for="type">Type</label>

                <select name="type" id="type" required>
                    <option value="" selected>-- Choose --</option>
                    <option value="cleaned">Normal text</option>
                    <option value="ner">Without named entities</option>
                    <option value="ner-ph">Replace named entities</option>
                    <option value="stop-list">Only stop words (vectorizer)</option>
                    <option value="stop">Only stop words (text)</option>
                    <option value="stop-ph">Content words replaced</option>
                </select>
            </div>

            <div class="field">
                <label>&nbsp;</label>
                <label>
                    <input type="checkbox" name="reinject_text" id="reinjectText" value="1" disabled>

                    Reinject text into result
                </label>
            </div>

            <div class="field" style='display: block;'>
                <label for="model">Model</label>

                <select name="model" id="model" disabled>
                    <option value="">-- Choose task/type first --</option>
                </select>
            </div>

            <textarea name="text" id="text" placeholder="Introdu textul aici..."></textarea>

            <div class="field">
                <label for="highlightLevel">
                    Highlighted features level:
                    <span id="highlightLevelValue">50</span>
                </label>

                <input type="range" name="detail_level" id="highlightLevel" min="1" max="100" value="50">
            </div>

            <div class="controls">
                <button type="submit" id="submitBtn">Incarca</button>
                <button type="button" id="button-random-entry" class="secondary">Text din baza de date</button>
            </div>
        </form>
    </div>

    <div>
        <div class="results-head">
            <button type="button" id="clearAllBtn" class="secondary">Clear all</button>
        </div>

        <div id="results"></div>
    </div>
</div>

<div class="loader" id="loader">
    <div class="spinner"></div>
</div>
<?= $footer ?>
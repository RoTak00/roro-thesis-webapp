<?= $head ?>

<div class="layout">
    <div>
        <h1>Detectie Ro / Md</h1>

        <form id="form">
            <textarea name="text" id="text" placeholder="Introdu textul aici..."></textarea>

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
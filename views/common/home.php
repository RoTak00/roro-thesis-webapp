<?= $head ?>

<div class="app-layout">
    <?= $navbar ?>

    <main class="app-content">
        <h1>Hello!</h1>
        <?php if (!empty($notification)) { ?>
            <div>
                <?= $notification ?>
            </div>
        <?php } ?>

    </main>
</div>
<?= $footer ?>
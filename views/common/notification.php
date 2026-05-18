<?php foreach ($notifications as $key => $notification) { ?>
    <div class="notification <?= $notification['type'] ?>" id="notification-<?= $key ?>">
        <div class="container"><?= $notification['message'] ?></div>
    </div>
<?php } ?>
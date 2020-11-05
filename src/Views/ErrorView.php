<?php $this->insert('Partials/Header', ['baseUri' => $baseUri, 'title' => $title]) ?>

<h1>⚠ Error</h1>
<p><?= $message ?></p>

<?php $this->insert('Partials/Footer') ?>

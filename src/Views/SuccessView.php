<?php $this->insert('Partials/Header', ['baseUri' => $baseUri, 'title' => $title]) ?>

<h1>Success! 😁</h1>
<p><?= $message ?></p>
<pre><?= json_encode($payload, JSON_PRETTY_PRINT) ?></pre>

<?php $this->insert('Partials/Footer') ?>

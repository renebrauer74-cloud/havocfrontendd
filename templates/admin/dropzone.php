<?php
/**
 * Bild-Upload mit Drag & Drop.
 * $dropLabel     Text in der Drop-Zone
 * $currentImage  bereits hinterlegtes Bild (relativer Pfad) oder null
 */
$currentImage ??= null;
?>
<div class="drop-zone" data-drop-zone>
    <span data-drop-label><?= e($currentImage ? 'Bild hinterlegt – neues Bild ablegen oder klicken' : $dropLabel) ?></span>
</div>
<input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" hidden data-drop-input>
<img class="preview-img" alt="Vorschau" data-drop-preview<?= $currentImage ? ' src="' . e(media_url($currentImage)) . '"' : ' hidden' ?>>
<?php $currentImage = null; ?>

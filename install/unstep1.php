<form action="<?= $APPLICATION->GetCurPage(); ?>">
	<?= bitrix_sessid_post(); ?>
	<input type="hidden" name="lang" value="<?= LANGUAGE_ID; ?>">
	<input type="hidden" name="id" value="ligacom.feed">
	<input type="hidden" name="uninstall" value="Y">
	<input type="hidden" name="step" value="2">
	<p><input type="checkbox" name="savedata" id="savedata" value="Y" checked><label for="savedata"><?= GetMessage('LIGACOM_FEED_UNINSTALL_SAVE_DATA')?></label></p>
	<input type="submit" name="inst" value="<?= GetMessage('LIGACOM_FEED_UNINSTALL_RUN'); ?>">
</form>
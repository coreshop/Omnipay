<form method="post" action="<?= $this->response->getRedirectUrl(); ?>" style="display:none;" id="redirect">
    <?php foreach($this->response->getRedirectData() as $key => $value) {
        ?>
        <input type="hidden" name="<?=$key?>" value="<?=$value?>" />
        <?php
    } ?>
</form>
<script type="text/javascript">
    $('#redirect').submit();
</script>
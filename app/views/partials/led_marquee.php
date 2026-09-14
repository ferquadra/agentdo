<?php
$ledList = !empty($ledMessages) ? $ledMessages : array('SIN VENCIMIENTOS PRÓXIMOS');
$ledJson = json_encode($ledList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($ledJson === false) {
    $ledJson = '["AGENTDO"]';
}
$ledAria = implode(' · ', $ledList);
?>
<div class="led-marquee-bar">
    <div
        class="js-led-marquee"
        data-messages="<?php echo e($ledJson); ?>"
        data-color="#f54e00"
    >
        <p class="visually-hidden"><?php echo e($ledAria); ?></p>
    </div>
</div>

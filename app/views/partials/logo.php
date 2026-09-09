<?php
if (!isset($logoClass)) {
    $logoClass = 'brand-logo';
}
if (!isset($logoAnimate)) {
    $logoAnimate = false;
}
?>
<a class="<?php echo e($logoClass); ?><?php echo $logoAnimate ? ' js-brand-hero' : ''; ?>" href="<?php echo e(url()); ?>">
    <span class="brand-word">
        <span class="brand-agent">Agent</span><span class="brand-do">Do</span><span class="brand-dot">.</span>
    </span>
    <span class="brand-underline" aria-hidden="true"></span>
</a>

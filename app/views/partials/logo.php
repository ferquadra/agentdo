<?php
if (!isset($logoClass)) {
    $logoClass = 'brand-logo';
}
if (!isset($logoAnimate)) {
    $logoAnimate = false;
}
?>
<?php $logoHref = Auth::check() ? url('panel') : url(); ?>
<a class="<?php echo e($logoClass); ?><?php echo $logoAnimate ? ' js-brand-hero' : ''; ?>" href="<?php echo e($logoHref); ?>">
    <span class="brand-word">
        <span class="brand-agent">Agent</span><span class="brand-do">Do</span><span class="brand-dot">.</span>
    </span>
    <span class="brand-underline" aria-hidden="true"></span>
</a>

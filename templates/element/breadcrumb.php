<?php
/**
 * The breadcrumb in the top bar, from the `breadcrumb` view variable: the
 * pages above the current one, each a link, ending in the page itself. On
 * phones only the page parent shows, as a back link.
 *
 * @var \Cake\View\View $this
 * @var list<array{title: string, url: array<string, mixed>|string|null}>|null $breadcrumb
 */
$breadcrumb = $breadcrumb ?? [];
$parent = count($breadcrumb) > 1 ? $breadcrumb[count($breadcrumb) - 2] : null;
?>
<nav class="kk-breadcrumb d-none d-md-block" aria-label="<?= h(__d('rock_software_ui', 'Breadcrumb')) ?>">
    <ol class="breadcrumb">
        <?php foreach ($breadcrumb as $i => $crumb) : ?>
            <?php $isLast = $i === count($breadcrumb) - 1; ?>
        <li class="breadcrumb-item<?= $isLast ? ' active' : '' ?>"<?= $isLast ? ' aria-current="page"' : '' ?>>
            <?php if ($crumb['url'] !== null && !$isLast) : ?>
                <?= $this->Html->link($crumb['title'], $crumb['url']) ?>
            <?php else : ?>
                <span class="<?= $isLast ? '' : 'kk-breadcrumb-text' ?>"><?= h($crumb['title']) ?></span>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ol>
</nav>
<div class="kk-breadcrumb d-md-none">
    <?php if ($parent !== null && $parent['url'] !== null) : ?>
        <?= $this->Html->link(
            '<i class="bi bi-chevron-left"></i><span>' . h($parent['title']) . '</span>',
            $parent['url'],
            ['class' => 'kk-breadcrumb-back', 'escapeTitle' => false],
        ) ?>
    <?php elseif ($breadcrumb !== []) : ?>
        <span class="kk-breadcrumb-back"><span><?= h($breadcrumb[count($breadcrumb) - 1]['title']) ?></span></span>
    <?php endif; ?>
</div>

<?php
/**
 * A fictional application's control in the top bar: a workspace picker.
 *
 * @var \Cake\View\View $this
 */
?>
<form method="get" action="/" class="d-flex align-items-center gap-2" id="demo-topbar-end">
    <label class="visually-hidden" for="demo-workspace">Workspace</label>
    <select id="demo-workspace" name="workspace" class="form-select form-select-sm w-auto">
        <option selected>North Example Ltd</option>
        <option>South Example Ltd</option>
    </select>
</form>

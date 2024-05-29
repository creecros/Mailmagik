<?= $this->form->hidden($name, array($name => '0')) ?>
<?= $this->form->checkbox ($name, $label, 1, isset($values[$name]) ? $values[$name] == 1 : $default) ?>

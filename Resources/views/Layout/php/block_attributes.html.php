<?php foreach ($attr as $k => $v): ?>
<?php printf('%s="%s" ', $view->escape($k), $view->escape($k === 'title' ? $view['translator']->trans($v, array(), $translation_domain) : $v)) ?>
<?php endforeach; ?>

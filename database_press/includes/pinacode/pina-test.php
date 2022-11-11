<?php
/*
add_action('init', function() {
Pinacode::set_var('nome', 'giulio');
print Pinacode::execute_shortcode('ciao [%nome] come [ok] va?,[^fn] [%bene?] speriamo [%nome]');

ob_start() ?>
<div class="pippo">[^SET post=[^post type="post"]]</div>
<ul>
[^FOR each=[%post]]
title: [%post.title]
[^ENDFOR]
</ul>
<?php
$block = ob_get_clean();
print "\n<p>-----------</p>\n";
print Pinacode::execute_shortcode($block);
print "\n<p>-----------</p>\n";
list($pre_string, $block, $post_string, $type) = pina_find_block($block, 0);
print ("<p>pre_string</p>");
var_dump ($pre_string);
print ("<p>block:</p>");
var_dump ($block);
print ("<p>TYPE: </p>");
var_dump ($type);
die;
});
*/

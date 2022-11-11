<?php 
/**
 * Calcola l'altezza del container per farlo entrare in altezza nella pagina
 * Il fle viene caricato in fondo da dbp-page-base.php
 * il container deve essere: <div id="dbp_container"
 * 
 */
if (!defined('WPINC')) die;
// Il popup che ti chiede se ti piace il plugin
$d = get_option('_dbp_activete_info');
if (is_array($d)) {
    if (isset($d['date'])) {
        $dd = \date_diff((new \DateTime($d['date'])), (new \DateTime()));
        if ($dd->days > 2 && $d['voted'] == 'no') {
            ?>
            <div class="dbp-vote-popup-background" id="dbp_vote_popup">
                <div class="dbp-vote-popup">
                    <div class="dbp_vote_popup_content">
                        <h2>If you like Database Press, please take a minute to rate the plugin.</h2>
                        <p> Thousands of plugins disappear every day due to indifference.</p>
                        <p> Help me keep this project alive, rate the plugin.</p>
                    </div>
                    <div class="dbp-vote-grid">
                        <div class="dbp-vote-cell" onclick="dbp_vote_plugin('already_vote')">I have already voted for it</div>
                        <div class="dbp-vote-cell" onclick="dbp_vote_plugin('dont_like')">I don't like it</div>
                        <div class="dbp-vote-cell" style="cursor: inherit" onclick="dbp_vote_plugin('dont_like')"><a href="https://wordpress.org/support/plugin/database_press/reviews/?filter=5" target="_blank">I'm going to vote</a></div>
                    </div>
                </div>
            </div>
            <?php
        }
    }
}
?>
<script>
    function dbp_set_container_height() {
        var h = window.innerHeight;  
        document.getElementById('dbp_container').style.height = (h  - 80) + "px";
        document.getElementById('dbp_container').style.width = (document.getElementById('wpbody-content').clientWidth  - 20) + "px";
    };
    dbp_set_container_height();
    window.addEventListener('resize', dbp_set_container_height);

    const resize_menu = new ResizeObserver(function(entries) {
        dbp_set_container_height();
    });
    resize_menu.observe( document.getElementById('adminmenu') );
</script>
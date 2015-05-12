<?php
/**
 * @package   Structured Content
 * @author    Phillihp Harmon <phillihpz@gmail.com>
 * @license   GPL-2.0+
 * @link      http://ctacorp.com
 * @copyright 2015 CTACorp
 */
?>

<style type="text/css">
table.oascData {
    margin: 15px;
}
table.oascData tr th {
    text-align: left;
}
table.oascData tr td {
    text-align: left;
    padding: 5px;
}
</style>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<form method="post" action="options.php">
    	
        <?php settings_fields( 'oasc-myoption-group' ); ?>
        <?php do_settings_sections( 'structured-content' ); ?>
        <?php submit_button(); ?>
        
    </form>
     <?php
    if(get_option('namespace-url')) {
        $contents = "";
        try {
            $contents = file_get_contents(get_option('namespace-url'));
        } catch(Exception $e) {
            //var_dump($e);
        }
        
        
        if($contents) {
            
            $xmlArray = simplexml_load_string($contents);
            ?>
            <h3>Content Types</h3>
            <div>Below is a list of defined data types from the origin.</div>
            <table class='oascData'>
            <tr><th>Type</th><th>Version</th><th>Last Modified</th></tr>
            <?php
            foreach($xmlArray as $key=>$val): ?>
                <?php $att = $val->attributes(); ?>
                <tr><td><?php echo $key; ?></td><td><?php echo $att['version']; ?></td><td><?php echo $att['lastModified']; ?></td></tr>
            <?php endforeach; ?>
            </table>
            
            <h3>Shared Content</h3>
            <div>Below are a list of sites that have been added to the Shared Content network.</div>
            <table class='oascData'>
            <tr><th>Name</th><th>Version</th><th>Source</th></tr>
            <?php
            foreach($xmlArray->SharedContent->Source as $key=>$val): ?>
                <?php $att = $val->attributes(); ?>
                <tr><td><?php echo $val->Name; ?></td><td><?php echo $att['version']; ?></td><td><?php echo $val->URL; ?></td></tr>
            <?php endforeach; ?>
            </table>
            <?php
        }
    }
    ?>
</div>
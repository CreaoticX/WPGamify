<?php
header('Content-Type: application/json'); 
# Issuer data
$issuer_origin_parts = parse_url( get_site_url() );
$issuer_origin_url = 'http://' . $issuer_origin_parts[ 'host' ];
$issuer_name = get_option( 'wpgamify_issuer_name' );
$issuer_org = get_option( 'wpgamify_issuer_org' );
$issuer_contact = get_option( 'wpgamify_issuer_contact' );
if (empty( $issuer_contact )){
    $issuer_contact = get_bloginfo( 'admin_email' );
}

?>
{
  "name": "<?php echo esc_js( $issuer_name ) ?>",
  "url": "<?php echo esc_js( $issuer_origin_url ) ?>",
  "email": "<?php echo esc_js( $issuer_contact ) ?>"
}
<?php

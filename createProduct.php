<?php
  include("pointer_api.php");
  $api = new pointer_api();
  $api->login('YOUR_API_USERNAME' , 'YOUR_API_PASSWORD');
  $api->productCreate('DOMAIN_NAME_FOR_PRODUCT', 'DURATION_IN_MONTHS', 0, 'PRODUCT_CODE', null);
  $api->logout();
?>

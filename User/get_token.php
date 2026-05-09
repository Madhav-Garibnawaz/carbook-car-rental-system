<?php
// get_token.php
$client_id = "96dHZVzsAuvniRh01YiSeVhmx8pGdVVTcJwYUfJPEMb2OgKcCePK8Rk85E2Yc_jsoyLTdtfS1-Rj1ZqPrmIZQg==";
$client_secret = "lrFxI-iSEg8wqnZ2w6eBY-9EeabZBksAyNMd18rMYe6HcnEs1k9_lOeReGgOo6itvpdz1cYlyCEx44C5KG6-NgwC8UacA0Op";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://outpost.mappls.com/api/security/oauth/token");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials&client_id=$client_id&client_secret=$client_secret");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

$response = curl_exec($ch);
curl_close($ch);
echo $response; // This returns the JSON containing the 'access_token'
?>
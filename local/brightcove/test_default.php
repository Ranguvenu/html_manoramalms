<?php
require_once('../../config.php');
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://ingest.api.brightcove.com/v1/accounts/5819061496001/videos/6340995132112/ingest-requests',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{
  "master":{
    "url":"https://support.brightcove.com/test-assets/videos/Great_Blue_Heron.mp4"
  },
  "profile":"multi-platform-extended-static",
  "capture-images": true
}',
  CURLOPT_HTTPHEADER => array(
    'Accept: application/json',
    'Content-Type: text/plain',
    'Authorization: Bearer AHF_pK3KZGl9zPRergpkNKDF3IWYEYPe2-ZIcAnULW4ELyox4hdQYt_YZIGz0lj30oQewYuzte_6TqivvypmDi598Dji6k2D7wWa2rXA7kBNUfA-cRSFliczsyLcNxk_quixiWgEMHsU6va0Dwc5jW5RDKWtIylueNr6MqorHSOhGOy4nGjHTtjtYFMvr6RH4nf4Jw8WE9BkD50q9kmUNyZyP_5jJDdps3xEhYnE7rNPd-1aHoMbpBfKwA91iSaADfPahRwEChQ5H6wJUOWpdhH4ILhsDn4hu_nDzp6yD-bNcsSgNNUwXIcJF4Sg3DQsl5CbFZUr21vougNVxigKKpEPEkY9HNf-4_Msp4smC4fRhrPEG0wJe-iB9-wmR8HaW9NY0_wWaWkgWk0zHLYOQO-ufN05Q2UXSw-GzYj5n_RIkunaI-P-WzhIGa37oD94IKqLHW26VrJMeo3PBwUJs6t2Dzb1yNrlnxLuIyEygJMvsSnQMhsUYNaRStbaQKSGl55GkCkVkrAhOb04eQf-WW5xCH7AMV1MHvUdP26nQ4Se-sF19zkI5Z0eYxPI3nA-9sKoBFx9X9dLTL1Hz0X1QV6bjzrF1_KkJ1Xa-cqeJBKvKMnoyUerjlN5VOooYgv7NVkZXKt420lfwise3CBGtUwBSvY2nrebSQvRl8YxWhMpW54tsDy0MLHKYkMLBLSPspYFt-sGEbZGFWCY_vEig0BUyObZM9SMTXK95cz_5Vk_Eq_hPvA9lUugI45YNRYXERt1ttpJvtcJeCFu9PI-vAjJXA4GlTB9G4x212W6GbAi_FQfnFB3tOynraWW5DJivEKBXdnpFdaQC10MePnu_1u-fpiXbpwVhu_bf91xguHKCXwNZolXLZrse2r7Dm1wiX2uVwrGkImVtKSVpZwQ_D284tfAbjo4ebGdNDlFDimBASBTvjIQ5fawtdUFFSNN2Jw4t-3J7BE0'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
var_dump($response);

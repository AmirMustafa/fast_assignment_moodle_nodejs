<?php

echo "<h2>Calling API using PHP cURL</h2>";

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://console.virtualwritingtutor.com/console/essay/test-feedback",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS =>"{\n\t\"test_link\": \"t2-argument essay\",\n\t\"text\": \"It is common belief that children should be educated a foreign language for children at primary school rather than secondary school. While this issue is beneficial to some extent, I strongly side with it. No doubt, it is much better for children to begin learning a foreign language at primary school because children are fast learners and acquiring a new language is good for their neurological development.\\\\nFirstly, at the primary school, children can learn faster than that at secondary school. It is true that children are between five and nine years old have the capacity to remember things fast twice as people in others age group. For instance, in China children can learn three languages such as German, English and France at an early age.\\\\nSecondly, learning a foreign language is helpful for developing a child’s brain. One research shown that learning any language could activate various new parts, which were never used before in their brain. Hence, School also have to play an important role to generate the suitable environment and methods for children learning language.\\\\nOn the other hand, at secondary school, pupils have to learn more subjects, which is compulsory in the curriculum such as Chemistry, Biology and History. Therefore, they have to spend time on learning more subjects, leading to the lack of time to learn another language. In addition, in modern days, it is necessary that children have to learn a foreign language. These days, workers prefer working at multinational company, where it has high salary and healthy working place. Hence, pupils have to being multilingual to have more job prospects in these companies.\\\\nTo sum up, instruction in a second language at elementary school is better than leaving it until later in life because kids learn new languages quickly when young and language learning has a beneficial affect on kids' growing brains. It is without doubt that despite a few burdensome aspects, this issue would still do more good than harm.\"\n}",
  CURLOPT_HTTPHEADER => array(
    "vwtapikey: 6a7edc3e-6cfa-11e7-97aa-00224d567b10",
    "Content-Type: application/json"
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;

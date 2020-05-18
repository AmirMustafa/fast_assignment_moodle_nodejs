<?php

echo "<h2>Calling Node JS API using NODE</h2>";


echo "<div id='json_response'><div>";


?>

<script type="text/javascript">
    function readTextFile(file, callback) {
        var rawFile = new XMLHttpRequest();
        rawFile.overrideMimeType("application/json");
        rawFile.open("GET", file, true);
        rawFile.onreadystatechange = function() {
            if (rawFile.readyState === 4 && rawFile.status == "200") {
                callback(rawFile.responseText);
            }
        }
        rawFile.send(null);
    }

    //usage:
    var API_KEY='';
    readTextFile("config.json", function(text){
        var data = JSON.parse(text);
        API_KEY=data.api_key;
    });
    
    setTimeout(() => {
        console.log(API_KEY);
   

    var myHeaders = new Headers();

    myHeaders.append("vwtapikey", API_KEY);
    myHeaders.append("Content-Type", "application/json");
    
    var raw = JSON.stringify({"test_link":"t2-argument essay","text":"It is common belief that children should be educated a foreign language for children at primary school rather than secondary school. While this issue is beneficial to some extent, I strongly side with it. No doubt, it is much better for children to begin learning a foreign language at primary school because children are fast learners and acquiring a new language is good for their neurological development.\\nFirstly, at the primary school, children can learn faster than that at secondary school. It is true that children are between five and nine years old have the capacity to remember things fast twice as people in others age group. For instance, in China children can learn three languages such as German, English and France at an early age.\\nSecondly, learning a foreign language is helpful for developing a child’s brain. One research shown that learning any language could activate various new parts, which were never used before in their brain. Hence, School also have to play an important role to generate the suitable environment and methods for children learning language.\\nOn the other hand, at secondary school, pupils have to learn more subjects, which is compulsory in the curriculum such as Chemistry, Biology and History. Therefore, they have to spend time on learning more subjects, leading to the lack of time to learn another language. In addition, in modern days, it is necessary that children have to learn a foreign language. These days, workers prefer working at multinational company, where it has high salary and healthy working place. Hence, pupils have to being multilingual to have more job prospects in these companies.\\nTo sum up, instruction in a second language at elementary school is better than leaving it until later in life because kids learn new languages quickly when young and language learning has a beneficial affect on kids' growing brains. It is without doubt that despite a few burdensome aspects, this issue would still do more good than harm."});
    
    var requestOptions = {
      method: 'POST',
      headers: myHeaders,
      body: raw,
      redirect: 'follow'
    };
    
    fetch("https://console.virtualwritingtutor.com/console/essay/test-feedback", requestOptions)
      .then(response => response.text())
      .then(result => {
          document.getElementById("json_response").innerHTML = result;
          console.log(result);
          
      })
      .catch(error => console.log('error', error));
      
    }, 100);
</script>
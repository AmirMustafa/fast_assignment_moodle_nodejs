var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
        }
    }
};

$(document).ready(function () {
    
    // Teacher Grade View CSS
    $(".path-mod-fastassignment [data-region='review-panel']").css({
        "display": "none"
    });
    
    
    
    $(".path-mod-fastassignment [data-region='grade-panel']").css({
        "position": "relative",
        "top": "0px",
        "bottom": "60px",
        "right": 0,
        "left": "0px",
        "width": "100%",
        "padding": "15px",
        "padding-top": 0,
        "margin-top": "108px"
    });
    
    $(".path-mod-fastassignment.pagelayout-embedded").css({
        "overflow": "auto"
    });
    
    $(".path-mod-fastassignment [data-region='grade-actions-panel'").css({
        "position": "relative"
    });
    
    let courseMod = getUrlParameter('id');
    let studentId = getUrlParameter('userid');
    // Prepare data for API
        $.ajax({
            url: "ajax_handler.php",
            data: {
                teacherView: 1,
                courseModule: courseMod,
                studentId: studentId
            },
            type: "GET",
            success: function(response) {
                response = JSON.parse(response, true);
            
                  const NODE_URL = "https://console.virtualwritingtutor.com/console";
                  const API_KEY = response.api_key;
                  const activity = response.activity;
                  
                    let testlink = response.test_links;
                    let test_name = response.test_name;
                    let category_name = response.category_name;
                    
                    let total_grammar_hits = response.grammar_hits;
                    let total_autoeval_student_hits = response.auto_eval_student;
                    let total_autoeval_teacher_hits = response.auto_eval_teacher;
                    
                    let used_grammar_hits = response.grammer_used_hits;
                    let autoeval_used_hits = response.autoeval_used_hits;
                    let dataToCheck = response.description;
                    if(dataToCheck !== null){
                       dataToCheck = dataToCheck.replace(/<(?:br|\/div|\/p)>/g, "\n").replace(/<.*?>/g, "");
                        dataToCheck = dataToCheck.replace(/&nbsp;/g,''); 
                    }
                        
                    console.log('dataToCheck: ',dataToCheck);
                    let teacherPermission = response.teacher_permission;
                    let isAdmin = response.admin;
                    
                    let remaining_grammar_hits = (total_grammar_hits - used_grammar_hits);
                    let remainingautoeval_hits = (total_autoeval_teacher_hits - autoeval_used_hits);
                    
                    if(teacherPermission === 1 && isAdmin === 0) {
                        $("#apimessage").html("No. of automated evaluations available: " + remainingautoeval_hits);
                    }
                    
                    
                    if(teacherPermission === 0 && isAdmin === 0) { $("#id_automatedevaluation").hide(); }
                
                  // Automated evaluation Async request
                  const automateEval = async (dataToCheck, testlink) => {
                    var myHeaders = new Headers();
                    myHeaders.append("vwtapikey", API_KEY);
                    myHeaders.append("Content-Type", "application/json");
                
                    var raw = JSON.stringify({
                      test_link: testlink,
                      text: dataToCheck,
                    });
                
                    var requestOptions = {
                      method: "POST",
                      headers: myHeaders,
                      body: raw,
                      redirect: "follow",
                    };
                
                    $("#loader").show();
                
                    const request = await (
                      await fetch(`${NODE_URL}/essay/test-feedback`, requestOptions)
                    ).json();
                    
                    // Recording teacher API track
                    $.ajax({
                        url: "ajax_handler.php",
                        data: {
                            api_triggered_teacher: 1,
                            courseModule: courseMod,
                            grammar_hits: 0,
                            autoeval_hits: 1
                        },
                        type: "GET",
                        success: function(response) {
                            response = JSON.parse(response, true);
                
                            remaining_grammar_hits = response.remaining_grammar_hits;
                            remainingautoeval_hits = response.remaining_autoeval_hits;
                            
                            if(teacherPermission == 1 && isAdmin == 0) {
                                $("#apimessage").html("No. of automated evaluations available: " + remainingautoeval_hits);
                            }
                        }
                    });
                
                    $("#loader").hide();
                    return request;
                  };
                
                  // Templates for automated evaluation - Section 1
                        let avgPragraphScore;
                        const getTemplate = (result, testlink, type) => {
                           // if(testlink === "t2-argument essay"){
                
                               // Get Statistics Info
                               if(type === "statistics") {
                                let wordCount = result.wordCount;
                                    
                                    let sentenceAvglength = result.sentenceCount.avg_length;
                                    let sentenceVariance = result.sentenceCount.variance;
                                    let sentenceCount = result.sentenceCount.count;
                
                                    let paragraphCount = result.paragraphCount;
                
                                    let feedbackValue = result.errorCount.feedback.value;
                                    let feedbackMsg = result.errorCount.feedback.msg;
                                    let feedbackScore = result.errorCount.feedback.score;
                
                                    avgPragraphScore = result.avgPragraphScore;
                                    avgPragraphScore = avgPragraphScore.toFixed(2);
                                    let testName = result.test_name;
                                    let bandScore = result.bandScore;
                                    let questionMarks = result.questionMarks;
                                    let firstPersonPronounCheck = result.firstPersonPronounCheck;
                                    let firstPersonPronounCheckText = firstPersonPronounCheck ? "have" : "have not";
                                    let assignmentScore = result.vocabularylist1Check.score;
                
                                    let template = `
                                    <div class="template_autoeval">
                                        <p dir="ltr" style="line-height:1.295;margin-top:0pt;margin-bottom:8pt;"><span style="font-size:24pt;font-family:Calibri,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">&ldquo;${test_name}&rdquo; Feedback to Display to Students using Automated evaluation feedback</span></p>
                                        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;"><span style="font-size:18pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:700;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">Assignment score: ${avgPragraphScore}%</span></p>
                                        <p><br></p>
                                        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;"><span style="font-size:13.999999999999998pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:700;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">Statistics</span></p>
                                        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;">&emsp;<span style="font-size:12pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">1. You have written ${wordCount} words.</span></p>
                                        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;">&emsp;<span style="font-size:12pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">2. I count a total of ${paragraphCount} paragraphs.</span></p>
                                        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;">&emsp;<span style="font-size:12pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">3. You have written ${sentenceCount} sentences.</span></p>
                                        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;">&emsp;<span style="font-size:12pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">4. You have written ${questionMarks} question.</span></p>
                                    
                                        </div>
                                        <div>
                                        <br />
                                        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;"><span style="font-size:13.999999999999998pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:700;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">Structure and content: ${avgPragraphScore}%</span></p>
                                    `;
                
                                    return template;
                                }
                
                                // Get paragraph info
                                if(type === "paragraph") {
                                    let paragraphWiseScoring = result.paragraphWiseScoring;
                                    let i = 1;
                                    
                                    for(key in paragraphWiseScoring){
                                        let outerData = paragraphWiseScoring[key];
                                        for(key2 in outerData){
                                            let innerData = outerData[key2];
                                            let innerValue = innerData.value;
                                            let innerMsg = innerData.msg;
                                            let innerScore = innerData.score;
                                            // template-append
                                            $('id_fastassignfeedbackcomments_editoreditable, #id_fastassignfeedbackcomments_editor, .validation').append(`
                                                    <br />
                                                        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;">&emsp;<span style="font-size:12.999999999999998pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:700;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">Paragraph ${i}</b> </span></p> <br />
                                                        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;">&emsp;<span style="font-size:12.999999999999998pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">${innerMsg}.</p>
                                                    </div>
                                            `);
                                            i++;
                                        }
                                    }
                                }
                
                                // Get vocab info
                                if(type === "vocabulary") {
                                    let vocabularylist1Check = result.vocabularylist1Check;
                                    let vocabularylist2Check = result.vocabularylist2Check;
                
                                    let vocabVal1 = vocabularylist1Check.value;
                                    let vocabMsg1 = vocabularylist1Check.msg.trim();
                                    let vocabScore1 = vocabularylist1Check.score;
                                    let vocabMatchedWords1 = vocabularylist1Check.matchedWords.toString().trim();
                                    
                                    let vocabVal2 = vocabularylist2Check.value;
                                    let vocabMsg2 = vocabularylist2Check.msg.trim();
                                    let vocabScore2 = vocabularylist2Check.score;
                                    let vocabMatchedWords2 = vocabularylist2Check.matchedWords.toString().trim();
                
                                    let template = `
                                    <div class="template_autoeval vocabulary">
                                        <br/><br/>
                                        <div class="template_autoeval vocab">
                                        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;"><span style="font-size:13.999999999999998pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:700;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">Vocabulary</span></p>
                
                                        <br>
                
                                        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;">
                                            &emsp;<span style="font-size:12.999999999999998pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;"><b>Argument-related words:</b>  ${vocabMatchedWords1} </span>
                                        </p>
                                        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;">
                                            &emsp;<span style="font-size:12.999999999999998pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;"><b>Feedback: </b> ${vocabMsg1} </span>
                                        </p>
                                        <br>
                
                                        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;">
                                            &emsp;<span style="font-size:12.999999999999998pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;"><b>Topic-related words:</b>  ${vocabMatchedWords2}</b> </span>
                                        </p>
                                        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;">
                                            &emsp;<span style="font-size:12.999999999999998pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;"><b>Feedback:</b>  ${vocabMsg2}</b> </span>
                                        </p>
                                            
                                        </p>
                                    </div>
                                    `;
                
                                    return template;
                                }
                
                                if(type === "scholarship") {
                                    let scholarship = result.worksCited;
                
                                    let scholarshipVal = scholarship.value;
                                    let scholarshipMsg = scholarship.msg;
                                    let scholarshipScore = scholarship.score;
                                    let scholarshipResponse = scholarship.value ? "Yes" : "No";
                
                                    let template = `
                                    <div class="template_autoeval vocabulary">
                                        <br/>
                                        <div class="template_autoeval vocab">
                                        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;"><span style="font-size:13.999999999999998pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:700;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">Scholarship</span></p>
                
                                        <br>
                
                                        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;">
                                            &emsp;<span style="font-size:12.999999999999998pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">Heading found:  ${scholarshipResponse} </span>
                                        </p>
                                        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;">
                                            &emsp;<span style="font-size:12.999999999999998pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">${scholarshipVal} works citied found:${scholarshipScore}% </span>
                                        </p>
                                    </div>
                                    `;
                
                                    return template;
                
                
                
                                }
                
                                if(type === "langaccuracy") {
                                    // Template second half
                                    let errorSuggestionLooper = result.errorCount.errorData.result;
                                    
                                    // template-append
                                    $('id_fastassignfeedbackcomments_editoreditable, #id_fastassignfeedbackcomments_editor, .validation').append(`
                                    <div class="template_autoeval vocabulary">
                                        <br/>
                                        <div class="template_autoeval vocab">
                                        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;"><span style="font-size:13.999999999999998pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:700;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">Language accuracy</span></p>
                
                                    `);
                
                                    errorSuggestionLooper.map((item, i) => {
                                        let feedback = item.feedback;
                                        let context = item.context;
                                        let markedText = item.marked_text;
                                        let suggestions = item.suggestions;
                                        let suggestionsString = item.suggestions.toString();
                
                                            // template-append
                                            $('id_fastassignfeedbackcomments_editoreditable, #id_fastassignfeedbackcomments_editor, .validation').append(`
                                            <br/>
                                            <ul class="language-accuracy-lists">
                                                <li><b>${i+1}. You wrote: </b>${context}</li>
                                                <li><b>Feedback: </b>${markedText}</li>
                                                <li><b>Error type: </b>${feedback}</li>
                                                <li><b>Suggestion: </b>${context}</li>
                                            </ul>
                                            </div>
                                        `);
                                    });
                                }
                                
                            // }
                        }
                
                  // Automated evaluation
                  $("#id_automatedevaluation").click(() => {
                    let checkAvailableAutoevalAPI = remainingautoeval_hits;
                    if(dataToCheck == null) {
                        $('.validation').html("");
                        $(".validation").show(); $(".success").hide();
                        $(".validation").show(); $(".success").hide();
                        $('.validation').append("<h5 style='color:#ff0000'>Automated evaluation feedback </h5><img src='pix/suggestion.jpg' alt='suggestion' width='30'> This student have not submitted his assignment yet.</h5><br/><br/>");
                        
                    } else {
                    
                        if(checkAvailableAutoevalAPI || isAdmin == 1) {
                            $(".validation").hide();
                        $(".success").hide();
                    
                        $(".validation").html("");
                        automateEval(dataToCheck, testlink).then(response =>{
                            
                                    let status = response.status;
                                    if(status){
                                        let result = response.result;
                    
                                        let wordCount = result.wordCount;
                                        
                                        let sentenceAvglength = result.sentenceCount.avg_length;
                                        let sentenceVariance = result.sentenceCount.variance;
                                        let sentenceCount = result.sentenceCount.count;
                    
                                        let paragraphCount = result.paragraphCount;
                    
                                        let feedbackValue = result.errorCount.feedback.value;
                                        let feedbackMsg = result.errorCount.feedback.msg;
                                        let feedbackScore = result.errorCount.feedback.score;
                    
                                        let avgPragraphScore = result.avgPragraphScore.toFixed(2);
                                        let testName = result.test_name;
                                        let bandScore = result.bandScore
                                        
                                        
                                        $("#id_grade").val(avgPragraphScore);
                                        
                                        // Writing Templates
                                        let templateStatistics = getTemplate(result, testlink, "statistics");
                                        $(".validation").show(); $(".success").hide();
                                        
                                        // template-append
                                        $('id_fastassignfeedbackcomments_editoreditable, #id_fastassignfeedbackcomments_editor, .validation').append(templateStatistics);
                                       
                    
                                        let templateParagraph = getTemplate(result, testlink, "paragraph");
                                        let templateVocab = getTemplate(result, testlink, "vocabulary");
                                       
                                        // template-append
                                        $('id_fastassignfeedbackcomments_editoreditable, #id_fastassignfeedbackcomments_editor, .validation').append(templateVocab);
                                        
                                        let templateScolarship = getTemplate(result, testlink, "scholarship");
                                        
                                        // template-append
                                        $('id_fastassignfeedbackcomments_editoreditable, #id_fastassignfeedbackcomments_editor, .validation').append(templateScolarship);
                                        
                                        let templateLangAccuracy = getTemplate(result, testlink, "langaccuracy");
                                        $(".language-accuracy-lists").css({
                                            "list-style-type": "none"
                                        });
                                        
                                        
                                       
                                        
                                    } 
                                    
                                    // when returns error from API
                                    else{
                                        let error = response.error;
                                        console.log("Error: " + error);
                                        $(".validation").show(); $(".success").hide();
                    
                                        $('.validation').append(`
                                            <h5 style='color:#ff0000'>Automated Evaluation: <img src='pix/suggestion.jpg' alt='suggestion' width='30'></h5>
                                            <p><b>Error from API: </b> <span style='color: #ff0000'>${error}</span></p>
                                        `);
                                        
                                        $('#id_fastassignfeedbackcomments_editoreditable, #id_fastassignfeedbackcomments_editor').append(`
                                            <h5 style='color:#ff0000'>Automated Evaluation: <img src='pix/suggestion.jpg' alt='suggestion' width='30'></h5>
                                            <p><b>Error from API: </b> <span style='color: #ff0000'>${error}</span></p>
                                        `);
                                    }
                                    
                                });
                        } else {
                            $('.validation').html("");
                            $(".validation").show(); $(".success").hide();
                            $(".validation").show(); $(".success").hide();
                            $('.validation').append("<h5 style='color:#ff0000'>Automated evaluation feedback </h5><img src='pix/suggestion.jpg' alt='suggestion' width='30'> Your maximum no. of checks for automated evaluation API is finished.</h5><br/><br/>");
                        }
                    }
                  });
            }
        });
});

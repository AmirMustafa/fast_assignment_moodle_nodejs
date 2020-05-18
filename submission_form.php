<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains the submission form used by the fastassignment module.
 *
 * @package   mod_fastassignment
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');
global $cm, $DB, $SESSION;

/**
 * fastassignment submission form
 *
 * @package   mod_fastassignment
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_fastassignment_submission_form extends moodleform {

    /**
     * Define this form - called by the parent constructor
     */
    public function definition() {
        global $USER, $cm, $DB;
        $mform = $this->_form;
        list($fastassignment, $data) = $this->_customdata;
        $instance = $fastassignment->get_instance();

        // Get API permissions
        $test_links_sql = $DB->get_record_sql("SELECT automatedgrammarcheck	, studentaccessibleautoeval FROM {fastassignment} WHERE id = $cm->instance");
        $grammar_permission = $test_links_sql->automatedgrammarcheck;
        $autoeval_permission = $test_links_sql->studentaccessibleautoeval;

        if ($instance->teamsubmission) {
            $submission = $fastassignment->get_group_submission($data->userid, 0, true);
        } else {
            $submission = $fastassignment->get_user_submission($data->userid, true);
        }
        if ($submission) {
            $mform->addElement('hidden', 'lastmodified', $submission->timemodified);
            $mform->setType('lastmodified', PARAM_INT);
        }

        $fastassignment->add_submission_form_elements($mform, $data);

        // Different APIs
        $mform->addElement('html', '<div class="validation"></div>');
        
        $mform->addElement('html', '<div class="success"></div>');
        $mform->addElement('html', '<div id="loader" style="display: none"><img src="pix/loader.gif"   alt="Loader" width="50"> Loading...</div>');
        
        $mform->addElement('html', '<div id="apimessage" style="margin-left: 82px;">Hello</div>');
        // $mform->addElement('button', 'validate_api_limits', get_string('validateapibutton', 'fastassignment'));
        
        if($grammar_permission){
            $mform->addElement('button', 'grammarcheck', get_string("grammarcheck", 'fastassignment'));
        }
        if($autoeval_permission){
            $mform->addElement('button', 'automatedevaluation', get_string("automatedevaluation", 'fastassignment'));
        }

        $this->add_action_buttons(true, get_string('savechanges', 'fastassignment'));
        if ($data) {
            $this->set_data($data);
        }
        
        
    }
}
$activity =  $cm->instance;
$SESSION->activity = $activity;

?>
<script src="https://code.jquery.com/jquery-3.4.1.min.js"> </script>
<script type="text/javascript">
    $(document).ready(function(){
        
        // Prepare data for API
        $.ajax({
            url: "ajax_handler.php",
            data: {
                studentView: 1
            },
            type: "GET",
            success: function(response) {
                response = JSON.parse(response, true);
                
                const NODE_URL = "https://console.virtualwritingtutor.com/console";
                const API_KEY = response.api_key;
                const activity = "<?php echo $activity ?>";
                
                let testlink = response.test_links;
                let test_name = response.test_name;
                let category_name = response.category_name;
                
                let total_grammar_hits = response.grammar_hits;
                let total_autoeval_student_hits = response.auto_eval_student;
                let total_autoeval_teacher_hits = response.auto_eval_teacher;
                
                let used_grammar_hits = response.grammer_used_hits;
                let autoeval_used_hits = response.autoeval_used_hits;
                
                console.log("Test links = " + testlink);
                
                let remaining_grammar_hits = (total_grammar_hits - used_grammar_hits);
                
                let remainingautoeval_hits = (total_autoeval_student_hits - autoeval_used_hits);
                $("#apimessage").html("No. of grammar checks available: " + remaining_grammar_hits + "<br/>No. of automated evaluations available: " + remainingautoeval_hits + "<br/><br/>");

                // Check Grammar Async request - Hitting grammar check Node API
                const checkGrammar = async (dataToCheck) => {
                    var myHeaders = new Headers();
                    myHeaders.append("vwtapikey", API_KEY);
                    myHeaders.append("Content-Type", "application/x-www-form-urlencoded");
                    myHeaders.append("Cookie", "PHPSESSID=46887039e4255a111bf7e1d3587df7cd");
        
                    var urlencoded = new URLSearchParams();
                    urlencoded.append("text", dataToCheck);
        
                    var requestOptions = {
                    method: 'POST',
                    headers: myHeaders,
                    body: urlencoded,
                    redirect: 'follow'
                    };
                    
                    $("#loader").show();
                    const request = await(await fetch(`${NODE_URL}/grammar/check-grammar?=`, requestOptions)).json();
                    
                    // After successful API, insert into table for tracking hits of users
                    $.ajax({
                        url: "ajax_handler.php",
                        data: {
                            api_triggered: 1,
                            grammar_hits: 1,
                            autoeval_hits: 0
                        },
                        type: "GET",
                        success: function(response) {
                            response = JSON.parse(response, true);
                
                            remaining_grammar_hits = response.remaining_grammar_hits;
                
                            remainingautoeval_hits = response.remaining_autoeval_hits;
                        }
                    });
                    
                    $("#apimessage").html("No. of grammar checks available: " + remaining_grammar_hits + "<br/>No. of automated evaluations available: " + remainingautoeval_hits + "<br/><br/>");
                    
                    $("#loader").hide();
                    return request;
                }
                
                // Check grammar
                $("#id_grammarcheck").click(() => {
                    
                    let checkAvailableGrammarAPI = remaining_grammar_hits;
                    
                    if(checkAvailableGrammarAPI > 0) {
                        
                        let dataToCheck = $("#id_onlinetext_editoreditable").html().replace(/<(?:br|\/div|\/p)>/g, "\n").replace(/<.*?>/g, "");
                            dataToCheck = dataToCheck.replace(/&nbsp;/g,"").replace(/&amp;/g, "&");
                        console.log('data: ',dataToCheck);
                        $(".validation").hide();
                        $(".success").hide();
                        
                        checkGrammar(dataToCheck).then(response =>{
                            let grammarResult = response.result.result
                            $('.validation').html("");
                            if(grammarResult.length > 0) {
                                $(".validation").show(); $(".success").hide();
                                $('.validation').append("<h5 style='color:#ff0000'>Grammar checker feedback </h5><img src='pix/suggestion.jpg' alt='suggestion' width='30'></h5>");
                                grammarResult.map((item, i) => {
                                    let markedText = item.marked_text;
                                    let feedback = item.feedback;
                                    let context = item.context;
                                    let suggestions = item.suggestions;
                                    let suggestionsString = item.suggestions.toString();
            
                                    $('.validation').append(`
                                        <br/>
                                        <ul class="grammar-lists">
                                            <li><b>${i+1}. You wrote: </b>${context}</li>
                                            <li><b>Feedback: </b>${markedText}</li>
                                            <li><b>Error type: </b>${feedback}</li>
                                            <li><b>Suggestion: </b>${suggestionsString}</li>
                                        </ul>
                                    `);
                                });
                                $('.validation').append("<br/>");
            
            
                            } else {
                                $(".validation").show(); $(".success").hide();
                                $('.validation').append("<h5 style='color:green'>Grammar checker feedback: <img src='pix/success.jpg' alt='green tick' width='30'></h5>");
                            }
                        })
                    } else {
                        $('.validation').html("");
                        $(".validation").show(); $(".success").hide();
                        $(".validation").show(); $(".success").hide();
                        $('.validation').append("<h5 style='color:#ff0000'>Grammar checker feedback </h5><img src='pix/suggestion.jpg' alt='suggestion' width='30'> Your maximum no. of checks for grammar checker API is finished.</h5><br/><br/>");
                    }
                });
        
                
        
                // Automated evaluation Async request
                const automateEval = async (dataToCheck, testlink) => {
                    var myHeaders = new Headers();
                    myHeaders.append("vwtapikey", API_KEY);
                    myHeaders.append("Content-Type", "application/json");
        
                    var raw = JSON.stringify({
                        "test_link":testlink,
                        "text":dataToCheck
                    });
        
                    var requestOptions = {
                    method: 'POST',
                    headers: myHeaders,
                    body: raw,
                    redirect: 'follow'
                    };
                    
                    
                    $("#loader").show();
                    const request = await(await fetch(`${NODE_URL}/essay/test-feedback`, requestOptions)).json();
                    
                    // After successful API, insert into table for tracking hits of users
                    $.ajax({
                        url: "ajax_handler.php",
                        data: {
                            api_triggered: 1,
                            grammar_hits: 0,
                            autoeval_hits: 1
                        },
                        type: "GET",
                        success: function(response) {
                            response = JSON.parse(response, true);
                            remaining_grammar_hits = response.remaining_grammar_hits;
                            remainingautoeval_hits = response.remaining_autoeval_hits;
                            
                            $("#apimessage").html("No. of grammar checks available: " + remaining_grammar_hits + "<br/>No. of automated evaluations available: " + remainingautoeval_hits + "<br/><br/>");
                        }
                    });
                    
                    $("#loader").hide();
                    return request;
                }
                
                // Argument Style Template
                const getTemplate1 = (result, testlink, type) => {
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
        
                            let avgPragraphScore = result.avgPragraphScore;
                                avgPragraphScore = avgPragraphScore.toFixed(2);
                            let testName = result.test_name;
                            let bandScore = result.bandScore;
                            let questionMarks = result.questionMarks;
                            let firstPersonPronounCheck = result.firstPersonPronounCheck;
                            let firstPersonPronounCheckText = firstPersonPronounCheck ? "have" : "have not";
                            let assignmentScore = result.vocabularylist1Check.score;
        
                            let template = `
                            <div class="template_autoeval">
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
                                    $('.validation').append(`
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
                            $('.validation').append(`
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
        
                                $('.validation').append(`
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
                }
                
                // Argument essay template
                const getTemplate2 = (result, testlink, type) => {
                    if(type === "score_writing_quality_quantity") {
                        let wordCount = result.wordCount;
                        let sentenceAvglength = result.sentenceCount.avg_length;
                        let sentenceVariance = result.sentenceCount.variance;
                        let sentenceCount = result.sentenceCount.count;
    
                        let paragraphCount = result.paragraphCount;
    
                        let feedbackValue = result.errorCount.feedback.value;
                        let feedbackMsg = result.errorCount.feedback.msg;
                        let feedbackScore = result.errorCount.feedback.score;
    
                        let avgPragraphScore = result.avgPragraphScore;
                            avgPragraphScore = avgPragraphScore.toFixed(2);
                        let testName = result.test_name;
                        let bandScore = result.bandScore;
                        let questionMarks = result.questionMarks;
                        let exclamationMarks = result.exclamationMarks;
                        let firstPersonPronounCheck = result.firstPersonPronounCheck;
                        let firstPersonPronounCheckText = firstPersonPronounCheck ? "have" : "have not";
                        let assignmentScore = result.vocabularylist1Check.score;
                        let clicheCount = result.clicheProfile.count;
    
                        let template= `<div class="argument_essay_template">
<p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;"><span style="font-size:18pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:700;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">Assignment score: ${avgPragraphScore}%</span></p>
<p><br></p>
<p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;"><span style="font-size:13.999999999999998pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:700;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">Writing quantity</span></p>
<p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;"><span style="font-size:12pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">1. You have written ${wordCount} words.</span></p>
<p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;"><span style="font-size:12pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">2. I count a total of ${paragraphCount} paragraphs.</span></p>
<p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;"><span style="font-size:12pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">3. You have written ${sentenceCount} sentences.</span></p>
<p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;"><span style="font-size:12pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">4. You have written ${questionMarks} question.</span></p>
<p><br></p>
<p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;"><span style="font-size:13.999999999999998pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:700;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">Writing quality</span></p>
<p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;"><span style="font-size:12pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">1. Your average sentence length is ${sentenceAvglength}.</span></p>
<p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;"><span style="font-size:12pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">2. Your sentence length variance is ${sentenceVariance}.</span></p>
<p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;"><span style="font-size:12pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">3. You have written ${clicheCount} clich&eacute;.</span></p>
<p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;"><span style="font-size:12pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">4. You have used ${exclamationMarks} exclamation mark.</span></p>
<p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;"><span style="font-size:12pt;font-family:'Times New Roman';color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">5. You ${firstPersonPronounCheckText} used 1 first-person pronoun (I, me, my, mine).</span></p>`;

return template;

                    }
                }
        
                // Templates for automated evaluation - Section 1
                const getTemplate = (result, testlink, type) => {
                   if(testlink === "t2-opinion essay"){
                        return getTemplate1(result, testlink, type);
                   }
                   
                   else if (testlink === "t2-argument essay"){
                       return getTemplate2(result, testlink, type);
                   }
                   
                   else {
                       return getTemplate1(result, testlink, type);
                   }
                }
        
                // Automated evaluation
                $("#id_automatedevaluation").click(() => {
                    let checkAvailableAutoevalAPI = remainingautoeval_hits;
                    if(checkAvailableAutoevalAPI > 0){
                        let dataToCheck = $("#id_onlinetext_editoreditable").html().replace(/\n/g,"").replace(/<(?:br|\/div|\/p)>/g, "\n").replace(/<.*?>/g, "");
                            dataToCheck = dataToCheck.replace(/&nbsp;/g,"");
                        console.log('data: ',dataToCheck);
                        $(".validation").hide();
                        $(".success").hide();
            
                        $('.validation').html("");
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
            
                                let avgPragraphScore = result.avgPragraphScore;
                                let testName = result.test_name;
                                let bandScore = result.bandScore
            
                                // Writing Templates
                                
                                //////////////////////////
                                //   OPINION ESSAY CALL
                                //////////////////////////
                                if(testlink === "t2-opinion essay"){
                                    let templateStatistics = getTemplate(result, testlink, "statistics");
                                    $(".validation").show(); $(".success").hide();
                                    $('.validation').append(templateStatistics);
                
                                    let templateParagraph = getTemplate(result, testlink, "paragraph");
                                    let templateVocab = getTemplate(result, testlink, "vocabulary");
                                    $('.validation').append(templateVocab);
                                    
                                    let templateScolarship = getTemplate(result, testlink, "scholarship");
                                    $('.validation').append(templateScolarship);
                
                                    let templateLangAccuracy = getTemplate(result, testlink, "langaccuracy");
                                }
                                
                                //////////////////////////
                                //   ARGUMENT ESSAY CALL
                                //////////////////////////
                                   
                                else if (testlink === "t2-argument essay"){
                                    let templateOpeningData = getTemplate2(result, testlink, "score_writing_quality_quantity");
                                    $(".validation").show(); $(".success").hide();
                                    $('.validation').append(templateOpeningData);
                                    
                                    
                                    
                                }
                                
                                /////////////////////////////
                                //   OTHER TEST-LINKS CALL
                                /////////////////////////////
                                
                                else {
                                    let templateStatistics = getTemplate(result, testlink, "statistics");
                                    $(".validation").show(); $(".success").hide();
                                    $('.validation').append(templateStatistics);
                
                                    let templateParagraph = getTemplate(result, testlink, "paragraph");
                                    let templateVocab = getTemplate(result, testlink, "vocabulary");
                                    $('.validation').append(templateVocab);
                                    
                                    let templateScolarship = getTemplate(result, testlink, "scholarship");
                                    $('.validation').append(templateScolarship);
                
                                    let templateLangAccuracy = getTemplate(result, testlink, "langaccuracy");
                                }
                                
                                
                               
                                
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
                            }
                            
                        });
                    } else{
                        $('.validation').html("");
                        $(".validation").show(); $(".success").hide();
                        $(".validation").show(); $(".success").hide();
                        $('.validation').append("<h5 style='color:#ff0000'>Automated evaluation feedback </h5><img src='pix/suggestion.jpg' alt='suggestion' width='30'> Your maximum no. of checks for automated evaluation API is finished.</h5><br/><br/>");
                    }
                });
        
            }
        });
});
</script>

<style>
   .form-group.row.fitem.femptylabel {
        *width: 20%;
        float: left;
    }
    .mt-5.mb-1.activity-navigation {
        width: 100%;
        float: left;
    }

    .validation, .success {
        margin-left: 215px;
    }
    
    .grammar-lists, .language-accuracy-lists {
        list-style-type: none;
    }
    
    
    input#id_cancel {
        position: absolute;
        top: 0px;
        left: 478px;
    }
    
    button#id_grammarcheck {
        width: 190px;
        position: absolute;
        top: 0px;
    }
    
    button#id_automatedevaluation {
       width: 228px;
       position: absolute;
       top: 0px;
       left: 157px;
    }
    
    input#id_submitbutton {
       position: absolute;
       left: 338px;
       top: 0px;
    }
    
    #loader img {
        vertical-align: middle;
        border-style: none;
        margin-left: 362px;
        margin-bottom: 10px;
    }

</style>

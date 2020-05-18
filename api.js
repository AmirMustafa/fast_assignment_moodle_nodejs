$(document).ready(function () {
    
    $.ajax({
        url: "../mod/fastassignment/ajax_handler.php",
        data: {
            settingsApi: 1
        },
        type: "GET",
        success: function(response) {
            response = JSON.parse(response, true);
            
            const NODE_URL = "https://console.virtualwritingtutor.com/console";
            const API_KEY = response.main_api;
              
              //////////////////////
              // CATEGORIES
              //////////////////////
            
              // Get category data from Node API
              let essayTasks = (essayTasksNew = essayTaskNew = "");
              const getCategories = async () => {
                  let myHeaders = new Headers();
                  myHeaders.append("vwtapikey", API_KEY);
                  let requestOptions = {
                    method: "GET",
                    headers: myHeaders,
                    redirect: "follow",
                  };
                  const request = await (
                    await fetch(`${NODE_URL}/essay/essay-tasks`, requestOptions)
                  ).json();
                  return request;
              };
            
              //////////////////////
              // CATEGORY TESTS
              //////////////////////
                const getCategoryTest = async (endpoint) => {
                  let myHeaders = new Headers();
                  myHeaders.append("vwtapikey", API_KEY);
                  let requestOptions = {
                    method: "GET",
                    headers: myHeaders,
                    redirect: "follow",
                  };
                  const request = await (
                    await fetch(`${NODE_URL}/essay/test-list/${endpoint}`, requestOptions)
                  ).json();
                  return request;
                };
                
                const getTestNfo = async (info_endpoint) => {
                  let myHeaders = new Headers();
                  myHeaders.append("vwtapikey", API_KEY);
                  let requestOptions = {
                    method: "GET",
                    headers: myHeaders,
                    redirect: "follow",
                  };
                  const request = await (
                    await fetch(
                      `${NODE_URL}/essay/test-info/${info_endpoint}`,
                      requestOptions
                    )
                  ).json();
                  return request;
                };
            
                const checkAPILimits = async () => {
                  let myHeaders = new Headers();
                  myHeaders.append("vwtapikey", API_KEY);
                  let requestOptions = {
                    method: "GET",
                    headers: myHeaders,
                    redirect: "follow",
                  };
                  const request = await (
                    await fetch(`${NODE_URL}/auth/info`, requestOptions)
                  ).json();
                  return request;
                };
            
                // ASYNC CODES
                essayTaskNew = getCategories().then((essayTaskNew) => {
                    // Prepare options of select category
                    let select = document.getElementById("id_category");
                    essayTaskNew.result.unshift({
                      task_name: "Select Category",
                      taskt_link: "#",
                    });
                    essayTaskNew.result.map((item, i) => {
                      let opt = document.createElement("option");
                      opt.value = i;
                      opt.innerHTML = item.task_name;
                      select.appendChild(opt);
                    });
            
                    // Set initial option value for tests
                    let loading = document.getElementById("id_test");
                    let opt = document.createElement("option");
                    opt.value = "";
                    opt.innerHTML = "Select Test";
                    loading.appendChild(opt);
            
                    // Prepare options of Tests
                    $("#id_category").change(function () {
                      let selectedCategory = $(this).children("option:selected").html();
                      // Create endpoint for tests
                      let endpointTest = selectedCategory.toLowerCase();
                      endpointTest = endpointTest.replace(/ /g, "-");
                      console.log("endpoint", endpointTest);
                      let data = getCategoryTest(endpointTest).then((data) => {
                        // reset prev options before on change
                        $("#id_test").find("option").remove().end();
                        // Prepare options of select category
                        let select = document.getElementById("id_test");
                        data.result.unshift({ test_name: "Select Tests", test_link: "#" });
                        data.result.map((item, i) => {
                          let opt = document.createElement("option");
                          opt.value = item.test_link;
                          opt.innerHTML = item.test_name;
                          select.appendChild(opt);
                        });
                      });
                    });
                });
                
                ///////////////////////////////////////////
                // APPENDING DATA IN EDITOR ON ADD CLICK
                //////////////////////////////////////////
                $("#id_add_to_editor").click(() => {
                let desc;
                let info_endpoint = $("#id_test").val();
                let projectURL = window.location.href;
                    projectURL = projectURL.split("course")[0];
                let ieltsChart = `<img src="${projectURL}mod/fastassignment/pix/ielts_test_chart.png" alt='IELTS Test 1 Chart' width='400'/>`;
                
                let request = getTestNfo(info_endpoint).then((data) => {
                let dataToSubmit = data.result.test_question;
                
                if( info_endpoint === "t1-task 1 test 1" || info_endpoint === "t1-task 1 test 2" || info_endpoint === "t1-task 1 test 3" || info_endpoint === "t1-task 1 test 4" || info_endpoint === "t1-task 1 test 5") {
                    dataToSubmit = dataToSubmit + "<p>"+ ieltsChart + "</p>";
                }
                  
                  $("#id_introeditoreditable").val();
                  $("#id_introeditoreditable").append(dataToSubmit);
                });
              });
                
                $("#id_add_to_editor")
                .prop("disabled", true)
                .prop("title", "you must select categories and tests first.")
                .css("cursor", "not-allowed");
                
                  let selected_test_val = "";
                  let selected_category_val = "";
                  $("select#id_test").change(function () {
                    $("#id_add_to_editor").prop("disabled", false).css("cursor", "pointer");
                    let selected_test = $(this).val();
                    let selected_testname = $("#id_test option:selected").text();
                    let selected_catname = $("#id_category option:selected").text();
                
                    $("input[name='testlinks_hidden']").val(selected_test);
                    $("input[name='test_name']").val(selected_testname);
                    $("input[name='category_name']").val(selected_catname);
                  });
                  $("select#id_category").change(function () {
                    $("#id_add_to_editor").prop("disabled", false).css("cursor", "pointer");
                    let selected_category = $(this).val();
                  });
                  // Validate API limit checks
                  $("#id_validate_api_limits").click(() => {
                    let apiKey = $("#id_api_key").val();
                    let apiPostUrl = $("#id_api_post_url").val();
                    let apiCategory = $("#id_category").val();
                    let api_handler_path = "<?php echo $api_handler_path ?>";
                
                    if (apiKey !== "") {
                      checkAPILimits().then((data) => {
                        const clientName = data.result.client_name;
                        const remainingGrammarHits = data.result.remaining_grammar_hits;
                        const remainingEssayHits = data.result.remaining_essay_hits;
                
                        const usedGrammarHits = data.result.used_grammar_hits;
                        const usedEssayHits = data.result.used_essay_hits;
                
                        const totalGrammarHits = data.result.total_grammar_hits;
                        const totalEssayHits = data.result.total_essay_hits;
                
                        const scoreGrammar = usedGrammarHits + "/" + totalGrammarHits;
                        const scoreEssay = usedEssayHits + "/" + totalEssayHits;
                
                        $("#id_validate_api_limits")
                          .html(
                            `
                                        Validated<br/> Client Name: ${clientName} <br> 
                                        Remaining Grammar hits:  ${remainingGrammarHits} hits remaining (${scoreGrammar}  used) <br/>
                                        Remaining Essay hits:  ${remainingEssayHits} hits remaining (${scoreEssay} used)`
                          )
                          .removeClass("btn btn-danger")
                          .addClass("btn btn-success");
                      });
                    } else {
                      $("#id_validate_api_limits")
                        .html("API key or category missing!")
                        .addClass("btn btn-danger");
                    }
                  });
      
        }
    });
});

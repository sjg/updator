<?php

	$github_username = "sjg";
	$github_project_repo = "updator";
	$github_project_path = "/Users/sjg/Sites/updator/";

	require_once('./colors.php');

	define("USE_CURL", true); 
	define("CLI", !isset($_SERVER['HTTP_USER_AGENT']));
	define("DEBUG", true);

	if(CLI){
		if($argc <=  3){
			echo "\n";
			echo "Usage: php updator.php [github_username] [github_project_repo] [Repository]\n";
			echo "where options include:\n";
			echo "\t Username:         Github Username \n";
			echo "\t Repository:       Github Reponame \n";
			echo "\t PATH:         	   Path to root project\n";
			echo "\n";
			exit(1);
		}else{
			$github_username = $argv[1];
			$github_project_repo = $argv[2];
			$github_project_path = $argv[3];
		}
	}else{
		//Get POST details as we are calling it within the Web Application
		if(!empty($_POST)){
			$github_username = $_POST["git_user"];
			$github_project_repo = $_POST["git_repo"];
			$github_project_path = $_POST["git_path"];
		}

		//Serve JSON Headers
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 18 Aug 2001 05:00:00 GMT');
		header('Content-type: application/json');
	}

	// Main Github URL for commits
	$url = "https://api.github.com/repos/".$github_username."/".$github_project_repo."/commits";

	// Scope out some variables
	$github_commit_content = "";
	$jsonOutput;

	if(USE_CURL){
		// I prefer to use CURL to fetch pages from the web
		$github_commit_content = get_url_content($url);
	}else{
		//Lets use fopen since we have it on the system
		$github_commit_content = file_get_contents($url); 
	}

	// Make the terminal output look slightly pretty
	$output = new Colors();

	if(!CLI){
		//Define array if were not in command line - saves a bit of memory
		$jsonOutput = array();
	}

	if($github_commit_content != ""){
			$commit_array = json_decode($github_commit_content, 1);
			
			$haveLocal = 0;
			$haveRemote = 0;

			if(array_key_exists("message", $commit_array)){
				if(CLI){
					echo "\n";
					echo $output->getColoredString("Github said: ".$commit_array["message"], "red", "black")."\n";
					echo "\n";
				}else{
					$jsonOutput["error"] = "Github said: ".$commit_array["message"];
					echo json_encode($jsonOutput);
				}
				exit(1);
			}

 			if(is_dir(getcwd().".git")){

 				if(CLI){
 					echo "\n";
					echo $output->getColoredString("Error: Could not find .git repository", "red", "black")."\n";
					echo "\n";
				}else{
					$jsonOutput["error"] = "Error: Could not find .git repository";
					
					if(DEBUG){
							$jsonOutput["debug"]["url"] = $url;
					}

					echo json_encode($jsonOutput);				
				}

				exit(1);
			}

			if(file_exists($github_project_path."/.git/refs/heads/master")){
				$haveLocal = 1;
			}

			if(file_exists($github_project_path."/.git/refs/remotes/origin")){
				$haveRemote = 1;
			}

			if(!$haveRemote && !$haveLocal){
				if(CLI){
					echo "\n";
					echo $output->getColoredString("Error: Could not find LOCAL HEAD or REMOTE to compare.", "red", "black")."\n";
					echo "\n";
				}else{
					$jsonOutput["error"] = "Error: Could not find LOCAL HEAD or REMOTE to compare";

					if(DEBUG){
							$jsonOutput["debug"]["url"] = $url;
					}

					echo json_encode($jsonOutput);
				}
				exit(1);
			}

			if($haveRemote){
				// Now check the remote respoistory for update
				$sha_local = file_get_contents($github_project_path."/.git/refs/remotes/origin/master", true);
			
				if(CLI){
					echo "\n";
				}

				if(strcmp(trim($sha_local), $commit_array[0]["sha"]) == 0){
					if(CLI){
						echo $output->getColoredString("Good News: Repository (MASTER) is up-to-date", "green", "black")."\n";
					}else{
						$jsonOutput["action"] = "none";
 						$jsonOutput["message"] = "Good News: Repository (MASTER) is up-to-date";
						$jsonOutput["color"] = "green";

						if(DEBUG){
							$jsonOutput["debug"]["url"] = $url;
							$jsonOutput["debug"]["local_sha"] = $url;
							$jsonOutput["debug"]["remote_sha"] = $url;
						}

						echo json_encode($jsonOutput);
					}
				}else{
					if(CLI){
						echo $output->getColoredString("Update Required", "red", "black")."\n";
						echo $output->getColoredString("Run: git pull", "green", "black")."\n";
					}else{
						$jsonOutput["action"] = "update";
 						$jsonOutput["message"] = "Update Required";
						$jsonOutput["color"] = "red";

						if(DEBUG){
							$jsonOutput["debug"]["url"] = $url;
							$jsonOutput["debug"]["local_sha"] = $url;
							$jsonOutput["debug"]["remote_sha"] = $url;
						}

						echo json_encode($jsonOutput);
					}
				}

				if(DEBUG){
					if(CLI){
						echo "\n";
						echo "       URL: ".$url."\n"; 
						echo " Local SHA: ".trim($sha_local)."\n";
						echo "Remote SHA: ".$commit_array[0]["sha"]."\n";
					}
				}

				if(CLI){
					echo "\n";
				}
			}
	}	


	// Some Functions to help us out
	// *****************************************************
	function get_url_content($url) {
  		$ch = curl_init();
  		$timeout = 5;
  		curl_setopt($ch, CURLOPT_URL, $url);
  		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
  		$data = curl_exec($ch);
  		curl_close($ch);
  		return $data;
	}


?>
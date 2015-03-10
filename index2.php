<?php
	$mysqli_gugel = new mysqli("localhost", "root", "root", "csearchdb");
	
	if ($mysqli_gugel->connect_errno){
		echo "Failed to connect to MySQL: (" . $mysqli_gugel->connect_errno . ") " . $mysqli_gugel->connect_error;
	}
	
	$searchtext = $_GET['searchtext'];
	$keywords = preg_split("/[\s\.]+/", $searchtext);

	if(isset($_GET['id'])){
		$id_plus = $_GET['id'];
		$update_priority_query1 = $mysqli_gugel->query("UPDATE course SET priority = priority + 0.01 WHERE id = ".$id_plus); 
	}

	//create temp table for scores
	$create_temp_table_query1 = $mysqli_gugel->query("CREATE TEMPORARY TABLE tablescores1 (
													temp_id int(8),
													temp_score float NOT NULL DEFAULT '0',
													temp_coursedesc text
													)");

	//create temp table for scores of algo2
	$create_temp_table_query2 = $mysqli_gugel->query("CREATE TEMPORARY TABLE tablescores2 (
													temp_id int(8),
													temp_score float NOT NULL DEFAULT '0'
													)");
	$len = count($keywords);


	/*ALGO1*/
	// loop. plus 1 to score for each word searched found in coursedesc
	for($aa = 0;$aa < count($keywords); $aa++){
		$search_word = $keywords[$aa];
		$match_query2 = $mysqli_gugel->query("SELECT * FROM course WHERE match(coursedesc) against ('$search_word')");
		$total_mq2 = $match_query2->num_rows;
		if($total_mq2 > 0){
			$row_mq2 = $match_query2->fetch_all();
			
			// loop for updating score in temp table
			// if id not found in temp table, insert it. else plus 1 to score
			for($bb = 0;$bb<$total_mq2;$bb++){
				$count_query1 = $mysqli_gugel->query("SELECT * FROM tablescores1 WHERE temp_id = ".$row_mq2[$bb][0]); 
				$total_cq1 = $count_query1->num_rows;
				
				if($total_cq1 > 0){
					$row_cq1 = $count_query1->fetch_all();
					$update_query1 = $mysqli_gugel->query("UPDATE tablescores1 SET temp_score = temp_score + 1 WHERE temp_id = ".$row_mq2[$bb][0]);
				}else{
					$insert_query1 = $mysqli_gugel->query("INSERT INTO tablescores1 (temp_id,temp_score) 
															VALUES (".$row_mq2[$bb][0].",1)");
				}
			}
		}
	}
	
	$match_query1 = $mysqli_gugel->query("SELECT * FROM course WHERE match(coursedesc) against ('$searchtext')");
	$total = $match_query1->num_rows;
	
	//loop for checking word order
	// searchtext: a b c d e
	// a b, b c, c d, d e -> plus 2/5
	// a b c, b c d, c d e -> plus 3/5
	
	if($total > 0){
		$row = $match_query1->fetch_all();
		for($j = 0;$j<$total;$j++){
			for($numofwords = 2;$numofwords <= count($keywords); $numofwords++){
				for($start = 0; $start < count($keywords) - $numofwords + 1;$start++){
					$searchme = "";
					for($x = 0;$x < $numofwords; $x++){
						$searchme = $searchme."".$keywords[$x+$start]." ";
					}
					
					$addme = $numofwords/count($keywords);
					$like_query1 = $mysqli_gugel->query("SELECT * FROM course WHERE coursedesc LIKE '%".$searchme."%' AND id = ".$row[$j][0]);
					$total_lq1 = $like_query1->num_rows;
					
					if($total_lq1 > 0){
						$update_query2 = $mysqli_gugel->query("UPDATE tablescores1 SET temp_score = temp_score + ".$addme." WHERE temp_id = ".$row[$j][0]);
					}
				}
			}
		}
	}

	$match_query3 = $mysqli_gugel->query("SELECT * FROM course c JOIN tablescores1 ts1 ON c.id = ts1.temp_id WHERE match(c.coursedesc) against ('$searchtext')");
	$total_mq3 = $match_query3->num_rows;

	if($total_mq3 > 0){
		$row = $match_query3->fetch_all();

		for($ii = 0;$ii<$total_mq3;$ii++){
			$newTotal= (float)$row[$ii][5]+(float)$row[$ii][7];
			$update_score_query1 = $mysqli_gugel->query("UPDATE tablescores1 SET temp_score = ".$newTotal." WHERE temp_id = ".$row[$ii][0]);
		}
	}

	$order_query1 = $mysqli_gugel->query("SELECT * 
											FROM course c 
												JOIN tablescores1 ts1 
												ON c.id = ts1.temp_id WHERE ts1.temp_score>0
											ORDER BY ts1.temp_score DESC");
	
	$total = $order_query1->num_rows;




	/*ALGO2*/
	/*iterate over the words in the search box*/
	for($i = 0;$i < $len; $i++){
		$query_algo2 = $mysqli_gugel->query("SELECT * FROM course WHERE match(coursedesc) against ('$searchtext')");
		$total_algo2 = $query_algo2->num_rows;
		
		if($total_algo2 > 0){ /*if match occurs*/
			$row_algo2 = $query_algo2->fetch_all();
			$word_score = $len - $i; /*score of current word*/
			
			/*iterate over rows*/
			for($j = 0;$j<$total_algo2;$j++){
				if($i==0){
					$finalscore[$j]=0;
				}	
				$desc = $row_algo2[$j][3];
				$occur = substr_count(strtoupper($desc), " ".strtoupper($keywords[$i])." ");
				
				if($occur==0){
					$occur = $occur+ substr_count(strtoupper($desc), strtoupper($keywords[$i])." ");
					if($occur==0){
						$occur = $occur+ substr_count(strtoupper($desc), " ".strtoupper($keywords[$i]).".");
					}
				}
				
				$occurrence_score = $occur * $word_score; // multiply occurence of a word to its score
				$finalscore[$j]=$finalscore[$j]+$occurrence_score;
			}
		}
	}


	if($total_algo2 > 0){
		$countTie = array_count_values($finalscore);

		for($i=0;$i<$total_algo2;$i++){
			if($countTie[$finalscore[$i]]>1){
					$finalscore[$i]=$finalscore[$i]+ (str_word_count($row_algo2[$i][3])/100);			
					$finalscore[$i]=$finalscore[$i]+ (strlen($row_algo2[$i][3])/1000);
			}
			else{
				$finalscore[$i]=$finalscore[$i]+0.01;	
			}
		}
		
		for($i=0;$i<$total_algo2;$i++){
			$insert_query2 = $mysqli_gugel->query("INSERT INTO tablescores2 (temp_id,temp_score) 
																VALUES (".$row_algo2[$i][0].",". $finalscore[$i].")");
		}
	}

	$order_query2 = $mysqli_gugel->query("SELECT * 
											FROM course c 
												JOIN tablescores2 ts2 
												ON c.id = ts2.temp_id WHERE ts2.temp_score>0
											ORDER BY ts2.temp_score DESC");
	$total_algo2 = $order_query2->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>CourSearch - Online Course Search</title>
    <meta name="description" content="Flat UI Kit Free is a Twitter Bootstrap Framework design and Theme, this responsive framework includes a PSD and HTML version."/>

    <meta name="viewport" content="width=1000, initial-scale=1.0, maximum-scale=1.0">

    <!-- Loading Bootstrap -->
    <link href="dist/css/vendor/bootstrap.min.css" rel="stylesheet">

    <!-- Loading Flat UI -->
    <link href="dist/css/flat-ui.css" rel="stylesheet">
    <link href="docs/assets/css/demo.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">

    <link rel="shortcut icon" href="img/favicon (1).ico">

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements. All other JS at the end of file. -->
    <!--[if lt IE 9]>
      <script src="dist/js/vendor/html5shiv.js"></script>
      <script src="dist/js/vendor/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <div class="container">
       
          <a href="index.html"><img src="img/logo3.png" style="height: 60px; width: 50px; float: left; margin-top: 20px;"></a><br/>
          <h4><div style="float: left; margin-right: 5px; font-weight: bolder;">C<i style="color: #f5c447; font-weight: bolder;">Search</i></div></h4>
       
      <form action="index2.php">
	      <div class="form-group">
	                  <div class="input-group">
	                    <input class="form-control" id="navbarInput-01" type="search" placeholder="Search" name = "searchtext">
	                    <span class="input-group-btn">
	                      <button type="submit" class="btn"><span class="fui-search"></span></button>
	                    </span>
	                  </div>
	        </div>
	  </form>
        <h7><i><?php echo "Search results for: <b>".$searchtext."</b><br/>"; ?>

        <?php echo "Total results: <b>".$total."</b>"; ?></i>
        </h7><br/>
          <div>
            <select class="form-control select select-primary algo" data-toggle="select">
              <option value="0">Algorithm 1</option>
              <option value="1">Algorithm 2</option>
              <option value="2">Algorithm 1 and 2</option>
            </select>
          </div>

	    <div class="algo1">
	    	<table width="100%">
			<?php
				if($total > 0){
					$row = $order_query1->fetch_all();
					for($i = 0;$i<$total;$i++){
						$rank = $i + 1;
						echo "<tr>
								<td width='89%' class='pbm' >
									<h5><a href='index2.php?searchtext=".$searchtext."&id=".$row[$i][0]."'>".$row[$i][1]."</a> (".$row[$i][4].".0 units)</h5>
									<h6>".$row[$i][2]."</h6>
									<small>".$row[$i][3]."</small>
								</td>
							<td class='pbm'>
								<dl class='palette palette-sun-flower'>
									<dt> <span class='fui-star-2'></span> ".$row[$i][7]."</dt>
								</dl>
							</td>
						</tr>";
					}
				}else{
					echo "No results found.";	
				}
			?>
		   	</table>
	    </div>

	    <div class="algo2">
	    	<table width="100%">
				<?php
					if($total_algo2 > 0)
					{
					//session_start();
					$row_algo2 = $order_query2->fetch_all();
						
						for($i = 0;$i<$total_algo2;$i++){
							
							echo "<tr>
									<td width='89%' class='pbm'>
										<h5><a href='index2.php?searchtext=".$searchtext."&id=".$row_algo2[$i][0]."'>".$row_algo2[$i][1]."</a> (".$row_algo2[$i][4].".0 units)</h5>
										<h6>".$row_algo2[$i][2]."</h6>
										<small>".$row_algo2[$i][3]."</small>
									</td>
									<td class='pbm'>
										<dl class='palette palette-sun-flower'>
										<dt> <div class='score'><center><span class='fui-star-2'></span> ".$row_algo2[$i][7]."</center></div></dt>
										</dl>
									</td>
								</tr>
					";
						}
					}
					else
					{
						echo "No results found.";	
					}
					
				?>
          	</table>
	    </div>

	    <div class="algo12">
	        <div style="width:49%;float: left; margin-right:2%;">
	          	<table width="100%">
				<?php
					if($total > 0){
						for($i = 0;$i<$total;$i++){
							$rank = $i + 1;
							echo "<tr>
									<td width='89%' class='pbm' >
										<h5><a href='index2.php?searchtext=".$searchtext."&id=".$row[$i][0]."'>".$row[$i][1]."</a> (".$row[$i][4].".0 units)</h5>
										<h6>".$row[$i][2]."</h6>
										<small>".$row[$i][3]."</small>
									</td>
								<td class='pbm'>
									<dl class='palette palette-sun-flower'>
										<dt> <div class='score'><center><span class='fui-star-2'></span> ".$row[$i][7]."</center></div></dt>
									</dl>
								</td>
							</tr>";
						}
					}else{
						echo "No results found.";	
					}
				?>
			   	</table>
	        </div>
	        <div id="table2">
		        <table width="49%" id="table2">
					<?php
						if($total_algo2 > 0)
						{
							
							for($i = 0;$i<$total_algo2;$i++){
								$desc2 = $row_algo2[$i][3];
								$search_array=preg_split("/[\s\.]+/", $search_word);
								$countLen=count($search_array);
								
								echo "<tr>
										<td width='89%' class='pbm'>
											<h5><a href='index2.php?searchtext=".$searchtext."&id=".$row_algo2[$i][0]."'>".$row_algo2[$i][1]."</a> (".$row_algo2[$i][4].".0 units)</h5>
											<h6>".$row_algo2[$i][2]."</h6>
											<small>".$desc2."</small>
										</td>
										<td class='pbm'>
											<dl class='palette palette-sun-flower'>
											<dt> <div class='score'><center><span class='fui-star-2'></span> ".$row_algo2[$i][7]."</center></div></dt>
											</dl>
										</td>
									</tr>
						";
							}
						}
						else
						{
							echo "No results found.";	
						}
						$mysqli_gugel->close();
						
					?>
	          	</table>
	        </div>
	    </div>
      </div>

    </div> <!-- /container -->
<?php

?>
    

    <script src="dist/js/vendor/jquery.min.js"></script>
    <script src="dist/js/vendor/video.js"></script>
    <script src="dist/js/flat-ui.min.js"></script>
    <script src="docs/assets/js/application.js"></script>
    <script src="link.js"></script>
    <script src="jquery-1.11.2.min.js"></script>

    <script>
      videojs.options.flash.swf = "dist/js/vendors/video-js.swf"
    </script>
  </body>
</html>

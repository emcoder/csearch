<?php
	$mysqli_gugel = new mysqli("localhost", "root", "", "cmsc191new");
	
	if ($mysqli_gugel->connect_errno){
		echo "Failed to connect to MySQL: (" . $mysqli_gugel->connect_errno . ") " . $mysqli_gugel->connect_error;
	}else{
		//echo "yay";
	}
	$searchtext = $_GET['searchtext'];
	
	//create temp table for scores
	$create_temp_table_query1 = $mysqli_gugel->query("CREATE TEMPORARY TABLE tablescores1 (
													temp_id int(8),
													temp_score float NOT NULL DEFAULT '0',
													temp_coursedesc text
													)");
	
	$keywords = preg_split("/[\s\.]+/", $searchtext);
	
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
		// gusto kong gawing bold yung search words sa coursedesc. di ko magawa huhu. unbold  nalang after ipakita
		/*	$update_query3 = $mysqli_gugel->query("UPDATE course 
													SET coursedesc = replace(coursedesc,'".$search_word."','<b>".$search_word."</b>')
													WHERE id = ".$row_mq2[$bb][0]);
		*/
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
						$update_query2 = $mysqli_gugel->query("UPDATE tablescores1 SET temp_score = temp_score + 0.1 WHERE temp_id = ".$row[$j][0]);
					}
				}
			}
		}
	}
	$order_query1 = $mysqli_gugel->query("SELECT * 
											FROM course c 
												JOIN tablescores1 ts1 
												ON c.id = ts1.temp_id
											ORDER BY ts1.temp_score DESC LIMIT 10");
	$total = $order_query1->num_rows;
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

    <link rel="shortcut icon" href="img/favicon (1).ico">

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements. All other JS at the end of file. -->
    <!--[if lt IE 9]>
      <script src="dist/js/vendor/html5shiv.js"></script>
      <script src="dist/js/vendor/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <div class="container">
       
          <img src="img/logo3.png" style="height: 60px; width: 50px; float: left; margin-top: 20px;"><br/>
          <h4><div style="float: left; margin-right: 5px; font-weight: bolder;">C<i style="color: #f5c447; font-weight: bolder;">Search</i></div></h4>
       
      
      <form action="index2.php">
      <div class="form-group">
                  <div class="input-group">

                    <input class="form-control" id="navbarInput-01" type="search" value="<?php echo $searchtext; ?>" name="searchtext">
                    <span class="input-group-btn">
                      <button type="submit" class="btn"><span class="fui-search"></span></button>
                    </span>
                  </div>
        </div>
      </form>
        <h7><i>Displaying Results <?php if($total >= 10){echo "10 of ".$total;}else{echo $total." of ".$total;}?></i></h7><br/>
          <div>
            <select class="form-control select select-primary" data-toggle="select">
              <option value="0">Algorithm 1</option>
              <option value="1">Algorithm 2</option>
              <option value="2">Algorithm 1 and 2</option>
            </select>
          </div> <!-- /.col-xs-3 -->
    <table width="100%">
		<?php
			if($total > 0){
				$row = $order_query1->fetch_all();
				for($i = 0;$i<$total;$i++){
					$rank = $i + 1;
					echo "<tr>
							<td width='89%' class='pbm' >
								<h5>".$row[$i][1]."</h5>
								<small>".$row[$i][3]."</small>
							</td>
						<td class='pbm'>
							<small style='font-weight: bold;'><center>".$row[$i][7]." pts</center></small>
							<dl class='palette palette-sun-flower'>
								<dt> <span class='fui-star-2'></span> Rank ". $rank ."</dt>
							</dl>
						</td>
					</tr>";
					//tanggaling yung nabold sa coursedesc
					/*
					$update_query4 = $mysqli_gugel->query("UPDATE course 
													SET coursedesc = replace(coursedesc,'<b>','')
													WHERE id = ".$row[$i][1]);
					$update_query4 = $mysqli_gugel->query("UPDATE course 
													SET coursedesc = replace(coursedesc,'</b>','')
													WHERE id = ".$row[$i][1]);
					*/
				}
			}else{
				echo "No results found.";	
			}
		?>
	   </table>
		  <center>
          <div class="pagination">
            <ul>
              <li class="previous"><a href="#fakelink" class="fui-arrow-left"></a></li>
              <li class="active"><a href="#fakelink">1</a></li>
              <li><a href="#fakelink">2</a></li>
              <li><a href="#fakelink">3</a></li>
              <li><a href="#fakelink">4</a></li>
              <li><a href="#fakelink">5</a></li>
              <li><a href="#fakelink">6</a></li>
              <li><a href="#fakelink">7</a></li>
              <li><a href="#fakelink">8</a></li>
              <li class="next"><a href="#fakelink" class="fui-arrow-right"></a></li>
            </ul>
          </div> <!-- /pagination -->
          </center>
      </div>

    </div> <!-- /container -->
<?php
	$mysqli_gugel->close();
?>
    

    <script src="dist/js/vendor/jquery.min.js"></script>
    <script src="dist/js/vendor/video.js"></script>
    <script src="dist/js/flat-ui.min.js"></script>
    <script src="docs/assets/js/application.js"></script>

    <script>
      videojs.options.flash.swf = "dist/js/vendors/video-js.swf"
    </script>
  </body>
</html>

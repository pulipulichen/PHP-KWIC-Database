<?php
require 'config.php';
require("rb.config.php");

// -----------------------

if (isset($_GET["bibliography_id"]) ) {
	$bibliography = R::load( 'bibliography', $_GET["bibliography_id"] );   //Retrieve
	if (isset($bibliography) ) {
		echo $bibliography->fulltext;
		?>
<style>
body {
	line-height: 1.5em;
	padding: 2em;
}
p {
	padding-left: 0 !important;
	line-height: 1.5em !important;
}
</style>
		<?php
		exit();
	}
} 	
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.2.2/jquery.min.js"></script>
<link rel="stylesheet" href="http://fontawesome.io/assets/font-awesome/css/font-awesome.css">
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.2.2/jquery.min.js"></script>
<link rel="stylesheet" href="//pulipulichen.github.io/blogger/posts/2016/12/semantic/semantic.min.css">
<link rel="stylesheet" href="http://fontawesome.io/assets/font-awesome/css/font-awesome.css">
<script src="//pulipulichen.github.io/blogger/posts/2016/12/semantic/semantic.min.js"></script>
<script src="//pulipulichen.github.io/blogger/posts/2016/12/jszip.min.js"></script>
<script src="//pulipulichen.github.io/blogger/posts/2016/12/FileSaver.min.js"></script>
<script src="//pulipulichen.github.io/blogger/posts/2016/11/r-text-mining/wordcloud2.js"></script>
<script src="//pulipulichen.github.io/blogger/posts/2016/12/clipboard.min.js"></script>
<script src="//pulipulichen.github.io/blogger/posts/2016/12/smooth-scroll.min.js"></script>
<!-- <link rel="icon" href="icon.png" type="image/png"> -->
<title><?php echo $CONFIG["title"]; ?></title>
<link rel="icon" type="image/png" href="favicon.png" />
<style>
body {
	padding: 2em;
}

.pointer {
	cursor: pointer;
	text-decoration: underline;
}

table td,
table th {
	vertical-align: top;
}

td strong {
	color: red;
}
/*
td.fulltext {
	text-align: center;
	white-space: nowrap;
	overflow-x: hidden;
	
}*/
td div.segment {
	text-align: center;
	padding-top: 0 !important;
    padding-bottom: 0 !important;
	white-space: nowrap;
}

td div.segment a {
	color: black;
	
}

.float-action-button {
  position: fixed;
  bottom: 1em;
  right: 1em;
  box-shadow: 0 0 4px rgba(0, 0, 0, 0.14), 0 4px 8px rgba(0, 0, 0, 0.28) !important;
}
</style>

</head>

<body>

<h1 id="top"><?php echo $CONFIG["title"]; ?></h1>
<form class="ui form" method="GET" action="index.php">
	<div class="ui segment">
		<div class="field">
			<label for="keyword">
				全文關鍵詞
			</label>
			<input type="text" id="keyword" name="keyword" 
                               placeholder="<?php echo $CONFIG["hint"]; ?>"
			<?php if(isset($_GET["keyword"])) { ?> value="<?php echo $_GET["keyword"]; ?>" <?php } ?> />
		</div> 
		<div class="field">
			<label for="title">
				標題
			</label>
			<input type="text" id="title" name="title" placeholder=""
			<?php if(isset($_GET["title"])) { ?> value="<?php echo $_GET["title"]; ?>" <?php } ?> />
		</div> 
		<div class="field">
			<label for="authors">
				作者
			</label>
			<input type="text" id="authors" name="authors" placeholder="" 
			<?php if(isset($_GET["authors"])) { ?> value="<?php echo $_GET["authors"]; ?>" <?php } ?> />
		</div> 
		<div class="field">
			<button type="submit" class="ui button blue fluid">查詢</button>
		</div>
	</div>
<?php
if (isset($_GET["keyword"]) || isset($_GET["title"]) || isset($_GET["authors"])) {
	@$keyword = $_GET["keyword"];
	@$search_title = $_GET["title"];
	@$search_authors = $_GET["authors"];
	// http://semantic-ui.com/elements/divider.html
	?>
<h2 class="ui horizontal divider header">查詢結果 </h2>
	<?php
	
// ---------------
// 開始查詢
include("simple_html_dom.php");

$cond = [];
$values = [];

if (isset($keyword) && $keyword !== "") {
	array_push($cond, 'fulltext LIKE :fulltext');
	$values[":fulltext"] = '%' . $keyword . '%';
}
if (isset($search_title) && $search_title !== "") {
	array_push($cond, 'title LIKE :title');
	$values[":title"] = '%' . $search_title . '%';
}
if (isset($search_authors) && $search_authors !== "") {
	array_push($cond, 'authors LIKE :authors');
	$values[":authors"] = '%' . $search_authors . '%';
}


//$bibliographies = R::find( 'bibliography'
//	, implode(" OR ", $cond)
//	, $values );
	
$bibliographies = R::getAll("SELECT * FROM bibliography WHERE " . implode(" OR ", $cond) . " ORDER BY date, title", $values);
//$bibliographies = R::getAll("SELECT * FROM bibliography WHERE fulltext LIKE '%中%' ORDER BY year, title");
//echo count($bibliographies);

$bibliographies = R::convertToBeans( 'bibliography', $bibliographies );
//echo "SELECT id, title, authors, date, fulltext FROM bibliography WHERE " //. implode(" OR ", $cond) ;

//echo implode(" OR ", $values);

$result_count =  count($bibliographies);
	?>
	<div class="ui info message">
		總共找到 <?php echo $result_count ?> 筆報導
	</div>
	<?php
	
// --------------------------
if ($result_count > 0) {
	?>

<button type="button" onclick="$(window).scrollTop(0)" class="circular large teal ui icon button float-action-button" title="回到頁首">
		<i class="large angle double up icon"></i>
</button>
<table class="ui striped table">
	<thead>
		<!-- <th>ID</th> -->
		<th>日期</th>
		<th>標題</th>
		<th>作者</th>
		<th>相關內文</th>
	</thead>
	<tbody>
	<?php
foreach ($bibliographies AS $bibliography) {
	$id = $bibliography->id;
	
	$fulltext = $bibliography->fulltext;
	$fulltext = str_get_html($fulltext)->plaintext;
	$fulltext_parts;
	if (isset($_GET["keyword"]) && $keyword !== "") {
		// 全文 > 依照新詞 斷開 成 很多陣列
		$fulltext_parts = explode($keyword, $fulltext);
		if (count($fulltext_parts) < 2) {
			continue;
		}
	}
	
	echo '<tr>';
	//echo '<td>' . $bibliography->id . '</td>';
	$date = $bibliography->date;
	echo '<td class="top aligned pointer" title="' . $date .'">' . substr($date, 0,4) . '</td>';
	
	$title = $bibliography->title;
	$ori_title = $title;
	if (mb_strlen($title) > 8 ) {
		$title = mb_substr($title, 0, 8) . "...";
	}
	$title = '<a href="index.php?bibliography_id=' . $id . '" target="_blank">' . $title . '</a>';
	echo '<td class="top aligned" title="'.$ori_title.'">' . $title . '</td>';
	
	//echo strpos("AAAVBBBVCCC", "V");	//4
	
	$authors = $bibliography->authors;
	$ori_authors = $authors;
	if (mb_strlen($authors) > 4 ) {
		$authors = mb_substr($authors, 0, 4) . "...";
	}
	echo '<td class="top aligned" title="' . $ori_authors . '">' . $authors . '</td>';
	//echo '<td>' . $bibliography->id . '</td>';
	
	// -----------------------
	
	$abstract_length = 20;
	if (isset($_GET["keyword"]) && $keyword !== "") {
		
		echo '<td class="fulltext top aligned center aligned">';
		//echo count($fulltext_parts);
		
		// Fulltext: AAAVBBBVCCC
		// New Term: V
		// Explode: ["AAA", "BBB", "CCC"]
		
		// foreach 0: continue
		// foreach 1: "AAA", "V", "BBB"
		// foreach 2: "BBB", "V", "CCC"
		
		
		foreach ($fulltext_parts AS $index => $part) {
			if ($index === 0) {
				continue;
			}
			
			
			echo '<div class="ui vertical  segment">';
			echo '<a href="index.php?bibliography_id=' . $id . '" target="_blank">';
			
			
			$last_part = $fulltext_parts[($index-1)];
			$last_part_len = mb_strlen($last_part);
			if ($last_part_len > $abstract_length) {
				$last_part = "..." . mb_substr($last_part, $last_part_len-$abstract_length, $abstract_length);
			}
			while (mb_strlen($last_part) < $abstract_length) {
				$last_part = "　" . $last_part;
			} 
			
			echo $last_part;
			
			// ---------
			
			echo '<strong>' . $keyword . '</strong>';
			
			$current_part = $part;
			$current_part_len = mb_strlen($current_part);
			if ($current_part_len > $abstract_length) {
				$current_part = mb_substr($current_part, 0, $abstract_length) . "...";
			}
			while (mb_strlen($current_part) < $abstract_length) {
				$current_part = $current_part . "　";
			} 
			echo $current_part;
			
			echo '</a>';
			
			echo '</div>';
		}
		echo '</td>';
	} 
	else {
		$pos = mb_strpos($fulltext, ".pdf");
		if ($pos !== FALSE) {
			$fulltext = mb_substr($fulltext, $pos + 4);
		}
		
		$pos = mb_strpos($fulltext, "版 ");
		if ($pos !== FALSE) {
			$fulltext = mb_substr($fulltext, $pos + 2);
		}
		
		$pos = mb_strpos($fulltext, "版</");
		if ($pos !== FALSE) {
			$fulltext = mb_substr($fulltext, $pos + 3);
		}
		
		$fulltext = mb_substr($fulltext, 0, $abstract_length*2) . "...";
		$fulltext = '<a href="index.php?bibliography_id=' . $id . '" target="_blank">' . $fulltext . '</a>';
		echo '<td>' . $fulltext . '</td>';
	}
	
	// -----------------
	echo '</tr>';
	
}
	?>
	</tbody>
</table>
	
	<?php
}	//if ($result_count > 0) {
	
}	// if (isset($_GET["keyword"])) {
?>
</form>

</body>
</html>

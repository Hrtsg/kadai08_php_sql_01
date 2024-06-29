<?php
//1.  DB接続します
try {
  //Password:MAMP='root',XAMPP=''
  $pdo = new PDO('mysql:dbname=gs_asgn08_db;charset=utf8;host=localhost','root','');
} catch (PDOException $e) {
  exit('DB_CONECT:'.$e->getMessage());
}

//２．データ登録SQL作成
$sql = "SELECT * FROM sample_sales";
$stmt = $pdo->prepare($sql);
$status = $stmt->execute(); //true or falseが入る

//３．データ表示
// $view="";　無視
if($status==false) {
  //execute（SQL実行時にエラーがある場合）
  $error = $stmt->errorInfo();
  exit("SQL_ERROR:".$error[2]);
}

//全データ取得
$values =  $stmt->fetchAll(PDO::FETCH_ASSOC); //PDO::FETCH_ASSOC[カラム名のみで取得できるモード]
//JSONい値を渡す場合に使う
$json = json_encode($values,JSON_UNESCAPED_UNICODE);

?>


<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>売上の予測分布</title>
<script src="https://d3js.org/d3.v7.min.js"></script>
<link rel="stylesheet" href="css/range.css">
<link href="css/bootstrap.min.css" rel="stylesheet">
<style>
  div{padding: 10px;font-size:16px;}
  td{border: 1px solid red;}
</style>
</head>
<body id="main">
<!-- Head[Start] -->
<header>
  <nav class="navbar navbar-default">
    <div class="container-fluid">
      <div class="navbar-header">
      <a class="navbar-brand" href="asgn08.php">データ表示</a>
      </div>
    </div>
  </nav>
</header>
<!-- Head[End] -->


<!-- Main[Start] -->
<div>
    <!-- <div class="container jumbotron"></div> -->
    <div class="table_all"></div>
    <table>
    <?php 
    echo "上から5行目までを出力 ID/客数/客単価/売上:\n";
    $first_five_values = array_slice($values, 0, 5);
    foreach($first_five_values as $value){ ?>
      <tr>
        <td><?=$value["ID"]?></td>
        <td><?=$value["customer"]?></td>
        <td><?=$value["sales_per_cust"]?></td>
        <td><?=$value["sales"]?></td>
      </tr>
        <?php } ?>
  </table>
</div>
<div class="table_kubun">
<?php 
    // データ区分（例：10000区切り）ごとにカウントするための配列を初期化
    $count_by_segment = [];
    // データを10000区切りでカウントする
    foreach($values as $value){
      // 区分を計算（例：1〜10000, 10001〜20000, ...）
    // var_dump($value["sales"]);
      $segment = ceil($value["sales"] / 10000);
    // var_dump($segment);
      // 区分ごとにカウントを増やす
      if (!isset($count_by_segment[$segment])) {
        $count_by_segment[$segment] = 0;
      }
      $count_by_segment[$segment]++;
    }?>
  <table>
    <?php
    // 区分ごとのカウント結果を昇順でソートする
    ksort($count_by_segment);
    // 結果を出力する
    echo "各区切りごとのカウント結果:\n";
    foreach ($count_by_segment as $segment => $count) {
      $start = ($segment - 1) * 10000 + 1;
      $end = $segment * 10000;
      //グラフ用にArrayに追加する
      $Graph_X[] = $end; //範囲 :X軸表示文字列
      $Graph_Y[] = $count;//度数 :Y軸の値
      ?>
      <tr>
        <td><?=$end?></td>
        <td><?=$count?></td>
      </tr>
      <!-- echo "区切り {$start} 〜 {$end}: {$count} 個\n"; -->
    <?php
    }?>
    <?php
    //pChartを利用したグラフ画像作成
    // MakeChart($Graph_X, $Graph_Y);
    ?>
  </table>
</div>
<div id="stage">
<?php 
function MakeChart($Graph_X, $Graph_Y)
{
	/*
	 Example3 : an overlayed bar graph, uggly no?
	*/

	// // Standard inclusions   
	include("./pChart/pData.class");
	include("./pChart/pChart.class");

	// Dataset definition 
	$DataSet = new pData;
	
	//描画データの設定
	//$DataSet->AddPoint(array(1,4,-3,2,-3,3,2,1,0,7,4,-3,2,-3,3,5,1,0,7),"Serie1");
	//$DataSet->AddPoint(array(0,3,-4,1,-2,2,1,0,-1,6,3,-4,1,-4,2,4,0,-1,6),"Serie2");
	$DataSet->AddPoint($Graph_X,"Value");
	$DataSet->AddPoint($Graph_Y,"Frequency");
	
	//X軸の値は数値でなく見出し文字列として表示
	$DataSet->SetAbsciseLabelSerie("Value");
	
	$DataSet->AddAllSeries();
	//$DataSet->SetAbsciseLabelSerie();
	//$DataSet->SetSerieName("January","Serie1");
	//$DataSet->SetSerieName("February","Serie2");


	// Initialise the graph
	$Test = new pChart(1280,960);
	$Test->setFontProperties("./Fonts/tahoma.ttf",8);
	$Test->setGraphArea(50,30,1200,900);
	//$Test->drawFilledRoundedRectangle(7,7,693,223,5,240,240,240);
	//$Test->drawRoundedRectangle(5,5,695,225,5,230,230,230);
	$Test->drawGraphArea(255,255,255,TRUE);
	$Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,0,2,TRUE);
	$Test->drawGrid(4,TRUE,230,230,230,50);

	//値を表示
	$Test->setFontProperties("./Fonts/tahoma.ttf",8);
	$Test->writeValues($DataSet->GetData(),$DataSet->GetDataDescription(),"frequency");
	
	// Draw the 0 line
	$Test->setFontProperties("./Fonts/tahoma.ttf",6);
	$Test->drawTreshold(0,143,55,72,TRUE,TRUE);

	// Draw the bar graph
	$Test->drawOverlayBarGraph($DataSet->GetData(),$DataSet->GetDataDescription());

	// Finish the graph
	$Test->setFontProperties("./Fonts/tahoma.ttf",8);
	$Test->drawLegend(600,30,$DataSet->GetDataDescription(),255,255,255);
	$Test->setFontProperties("./Fonts/tahoma.ttf",10);
	$Test->drawTitle(50,22,"Nicommunity frequency distribution chart",50,50,50,585);
	$Test->Render("example3.png");//PNGファイルとして出力される

}
?>
</div>

<!-- Main[End] -->


<script>
  //JSON受け取り
      $a = '<?=$json?>';
      const obj = JSON.parse($a);
      console.log(obj);

      var data_sales = {obj};

      var chart = c3.generate({
        bindto:'#stage',
        data: data_sales,
      });

        //     const margin = { top: 20, right: 30, bottom: 40, left: 40 },
        //         width = 800 - margin.left - margin.right,
        //         height = 400 - margin.top - margin.bottom;

        //     const svg = d3.select(containerId).append("svg")
        //         .attr("width", width + margin.left + margin.right)
        //         .attr("height", height + margin.top + margin.bottom)
        //         .append("g")
        //         .attr("transform", `translate(${margin.left},${margin.top})`);
            
        //     // x軸とy軸のスケールを設定
        //     const x = d3.scaleBand()
        //         .domain(filteredData.map(d => d["会社名"]))
        //         .range([0, width])
        //         .padding(0.1);

        //     const y = d3.scaleLinear()
        //         .domain([0, d3.max(filteredData, d => d[elementJpName])])
        //         .nice()
        //         .range([height, 0]);

        //         // x軸とy軸をSVGに追加
        //     svg.append("g")
        //         .selectAll(".bar")
        //         .data(filteredData)
        //         .enter().append("rect")
        //         .attr("class", "bar")
        //         .attr("x", d => x(d["会社名"]))
        //         .attr("y", d => y(d[elementJpName]))
        //         .attr("width", x.bandwidth())
        //         .attr("height", d => height - y(d[elementJpName]));

        //     svg.append("g")
        //         .attr("transform", `translate(0,${height})`)
        //         .call(d3.axisBottom(x))
        //         .append("text")
        //         .attr("y", margin.bottom / 2)
        //         .attr("x", width / 2)
        //         .attr("text-anchor", "middle")
        //         .attr("class", "axis-label")
        //         .text("会社名");

        //     svg.append("g")
        //         .call(d3.axisLeft(y))
        //         .append("text")
        //         .attr("transform", "rotate(-90)")
        //         .attr("y", -margin.left)
        //         .attr("x", -height / 2)
        //         .attr("text-anchor", "middle")
        //         .attr("class", "axis-label")
        //         .text(elementJpName);
        // }

</script>
</body>
</html>
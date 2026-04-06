<?php header("Content-Type:text/html;charset=utf-8"); ?>
<?php //error_reporting(E_ALL | E_STRICT);
##-----------------------------------------------------------------------------------------------------------------##
#
#  PHP工房メールフォームプログラム【MailForm_Full】　全機能搭載版 （ファイル添付は要PHP5以上）ver2.0.4 最終更新日2024/05/24
#　改造や改変は自己責任で行ってください。
#	
#  今のところ特に問題点はありませんが、不具合等がありましたら下記までご連絡ください。
#  MailAddress: info@php-factory.net
#  name: K.Numata
#  HP: http://www.php-factory.net/
#
#  重要！！サイトでチェックボックスを使用する場合のみですが。。。
#  チェックボックスを使用する場合はinputタグに記述するname属性の値を必ず配列の形にしてください。
#  例　name="当サイトをしったきっかけ[]"  として下さい。
#  nameの値の最後に[と]を付ける。じゃないと複数の値を取得できません！
#
##-----------------------------------------------------------------------------------------------------------------##
if (version_compare(PHP_VERSION, '5.1.0', '>=')) {//PHP5.1.0以上の場合のみタイムゾーンを定義
	date_default_timezone_set('Asia/Tokyo');//タイムゾーンの設定（日本以外の場合には適宜設定ください）
}

/*-------------------------------------------------------------------------------------------------------------------
* ★以下設定時の注意点　
* ・値（=の後）は数字以外の文字列（一部を除く）はダブルクオーテーション「"」、または「'」で囲んでいます。
* ・これをを外したり削除したりしないでください。後ろのセミコロン「;」も削除しないください。
* ・また先頭に「$」が付いた文字列は変更しないでください。数字の1または0で設定しているものは必ず半角数字で設定下さい。
* ・メールアドレスのname属性の値が「Email」ではない場合、以下必須設定箇所の「$Email」の値も変更下さい。
* ・name属性の値に半角スペースは使用できません。
*以上のことを間違えてしまうとプログラムが動作しなくなりますので注意下さい。
-------------------------------------------------------------------------------------------------------------------*/


//---------------------------　必須設定　必ず設定してください　-----------------------

//サイトのトップページのURL　※デフォルトでは送信完了後に「トップページへ戻る」ボタンが表示され、そのリンク先です。
$site_top = "https://koyururi-jinja.com/";

//管理者のメールアドレス（送信先） ※メールを受け取るメールアドレス(複数指定する場合は「,」で区切ってください 例 $to = "aa@aa.aa,bb@bb.bb";)
$to = "contact@koyururi-jinja.com";


//送信元（差出人）メールアドレス（管理者宛て、及びユーザー宛メールの送信元（差出人）メールアドレスです）
//必ず実在するメールアドレスでかつ出来る限り設置先サイトのドメインと同じドメインのメールアドレスとしてください（でないと「なりすまし」扱いされます）
//管理者宛てメールの返信先（reply）はユーザーが入力したメールアドレスになりますので返信時はユーザーのメールアドレスが送信先に設定されます）
$from = "contact@koyururi-jinja.com";

//管理者宛メールの送信元（差出人）にユーザーが入力したメールアドレスを表示する(する=1, しない=0)
//ユーザーのメールアドレスを含めることでメーラー上で管理しやすくなる機能です。
//例 example@gmail.com <from@sample.jp>（example@gmail.comがユーザーメールアドレス、from@sample.jpが↑の$fromで設定したメールアドレスです）
$from_add = 1;

//フォームのメールアドレス入力箇所のname属性の値（name="○○"　の○○部分）
$Email = "Email";
//---------------------------　必須設定　ここまで　------------------------------------


//---------------------------　セキュリティ、スパム防止のための設定　------------------------------------

//スパム防止のためのリファラチェック（フォーム側とこのファイルが同一ドメインであるかどうかのチェック）(する=1, しない=0)
//※有効にするにはこのファイルとフォームのページが同一ドメイン内にある必要があります
$Referer_check = 0;

//リファラチェックを「する」場合のドメイン ※設置するサイトのドメインを指定して下さい。
//もしこの設定が間違っている場合は送信テストですぐに気付けます。
$Referer_check_domain = "koyururi-jinja.com";

/*セッションによるワンタイムトークン（CSRF対策、及びスパム防止）(する=1, しない=0)
※ただし、この機能を使う場合は↓の送信確認画面の表示が必須です。（デフォルトではON（1）になっています）
※【重要】ガラケーは機種によってはクッキーが使えないためガラケーの利用も想定してる場合は「0」（OFF）にして下さい（PC、スマホは問題ないです）*/
$useToken = 1;
//---------------------------　セキュリティ、スパム防止のための設定　ここまで　------------------------------------


//---------------------- 任意設定　以下は必要に応じて設定してください ------------------------

// Bccで送るメールアドレス(複数指定する場合は「,」で区切ってください 例 $BccMail = "aa@aa.aa,bb@bb.bb";)
$BccMail = "";

// 管理者宛に送信されるメールのタイトル（件名）
$subject = "こゆるり神社 ホームページのお問い合わせ";

// 送信確認画面の表示(する=1, しない=0)
$confirmDsp = 1;

// 送信完了後に自動的に指定のページ(サンクスページなど)に移動する(する=1, しない=0)
// CV率を解析したい場合などはサンクスページを別途用意し、URLをこの下の項目で指定してください。
// 0にすると、デフォルトの送信完了画面が表示されます。
$jumpPage = 1;

// 送信完了後に表示するページURL（上記で1を設定した場合のみ）※httpから始まるURLで指定ください。（相対パスでも基本的には問題ないです）
$thanksPage = "thanks.html";

// 必須入力項目を設定する(する=1, しない=0)
$requireCheck = 0;

/* 必須入力項目(入力フォームで指定したname属性の値を指定してください。（上記で1を設定した場合のみ）
値はシングルクォーテーションで囲み、複数の場合はカンマで区切ってください。フォーム側と順番を合わせると良いです。 
配列の形「name="○○[]"」の場合には必ず後ろの[]を取ったものを指定して下さい。*/
$require = array('お名前','電話番号','Email','利用規約、プライバシー・ポリシー同意');


//----------------------------------------------------------------------
//  自動返信メール設定(START)
//----------------------------------------------------------------------

// 差出人に送信内容確認メール（自動返信メール）を送る(送る=1, 送らない=0)
// 送る場合は、フォーム側のメール入力欄のname属性の値が上記「$Email」で指定した値と同じである必要があります
$remail = 1;

//自動返信メールの送信者欄に表示される名前　※あなたの名前や会社名など（もし自動返信メールの送信者名が文字化けする場合ここは空にしてください）
$refrom_name = "";

// 差出人に送信確認メールを送る場合のメールのタイトル（上記で1を設定した場合のみ）
$re_subject = "こゆるり神社 送信ありがとうございました";

//フォーム側の「名前」箇所のname属性の値　※自動返信メールの「○○様」の表示で使用します。
//指定しない、または存在しない場合は、○○様と表示されないだけです。あえて無効にしてもOK
$dsp_name = 'お名前';

//自動返信メールの冒頭の文言 ※日本語部分のみ変更可
$remail_text = <<< TEXT

お問い合わせありがとうございました。
担当者が確認後にご返信致しますので今しばらくお待ちください。

送信内容は以下になります。

TEXT;


//自動返信メールに署名（フッター）を表示(する=1, しない=0)※管理者宛にも表示されます。
$mailFooterDsp = 1;

//上記で「1」を選択時に表示する署名（フッター）（FOOTER～FOOTER;の間に記述してください）
$mailSignature = <<< FOOTER

＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝
こゆるり神社
〒238-0243 神奈川県三浦市三崎1丁目10-10
お問い合わせ：contact@koyururi-jinja.com
URL: https://koyururi-jinja.com/
＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝

FOOTER;


//----------------------------------------------------------------------
//  自動返信メール設定(END)
//----------------------------------------------------------------------

//メールアドレスの形式チェックを行うかどうか。(する=1, しない=0)
//※デフォルトは「する」。特に理由がなければ変更しないで下さい。メール入力欄のname属性の値が上記「$Email」で指定した値である必要があります。
$mail_check = 1;

//全角英数字→半角変換を行うかどうか。(する=1, しない=0)
$hankaku = 0;

//全角英数字→半角変換を行う項目のname属性の値（name="○○"の「○○」部分）
//※複数の場合にはカンマで区切って下さい。（上記で「1」を指定した場合のみ有効）
//配列の形「name="○○[]"」の場合には必ず後ろの[]を取ったものを指定して下さい。
$hankaku_array = array('電話番号','金額');

//-fオプションによるエンベロープFrom（Return-Path）の設定(する=1, しない=0)　
//※宛先不明（間違いなどで存在しないアドレス）の場合に 管理者宛に「Mail Delivery System」から「Undelivered Mail Returned to Sender」というメールが届きます。
//サーバーによっては稀にこの設定が必須の場合もあります。
//設置サーバーでPHPがセーフモードで動作している場合は使用できませんので送信時にエラーが出たりメールが届かない場合は「0」（OFF）として下さい。
$use_envelope = 0;

//機種依存文字の変換
/*たとえば㈱（かっこ株）や①（丸1）、その他特殊な記号や特殊な漢字などは変換できずに「？」と表示されます。それを回避するための機能です。
確認画面表示時に置換処理されます。「変換前の文字」が「変換後の文字」に変換され、送信メール内でも変換された状態で送信されます。（たとえば「㈱」の場合、「（株）」に変換されます） 
必要に応じて自由に追加して下さい。ただし、変換前の文字と変換後の文字の順番と数は必ず合わせる必要がありますのでご注意下さい。*/

//変換前の文字
$replaceStr['before'] = array('①','②','③','④','⑤','⑥','⑦','⑧','⑨','⑩','№','㈲','㈱','髙');
//変換後の文字
$replaceStr['after'] = array('(1)','(2)','(3)','(4)','(5)','(6)','(7)','(8)','(9)','(10)','No.','（有）','（株）','高');

//----------------------------------------------------------------------
// CSV保存用設定 （START）
//----------------------------------------------------------------------

//初回送信時にファイルが自動生成されます。また初回送信時にのみフォームの各項目名をフォームページの順番で1行目に書き込みます。

/*
【重要】 フォームにチェックボックス、またはラジオボタンがある場合で、かつ必須ではない場合、空のまま送信できてしまいますが、
そうすると2回目以降でCSVデータの列がずれます。（登録データには影響ありません）※未選択の場合POSTすらされないためです。
チェックボックス、ラジオボタンがある場合で、かつ必須ではない場合、列ズレを防ぐため下記「CSVに保存する項目を指定する」でCSVに保存したい項目すべてのname属性の値を指定して下さい。
*/

//データをリセット、またはやり直したい場合にはサーバー上のdata.csvファイルを削除すればOKです。初回送信時に自動生成されます。


//CSVに保存する(する=1, しない=0)　※「する」場合、dataフォルダを書き込み可能なパーミッション（777等※サーバによる）に変更ください。
$csv_backup = 0;

//CSV保存先ディレクトリ（書き込み可能なパーミッション（777等※サーバによる）に変更ください）
$csv_dir = "data/";

//CSV保存ファイル名
$csv_filename = "data.csv";

//CSVファイルパス（変更禁止）
$csv_file_path = $csv_dir.$csv_filename;

//各データの先頭に「0」が含まれていたら「"」の前に「=」を追記する(する=1, しない=0) 
//エクセルで先頭の0（特に電話番号で）が消える問題対策 ※CSVをその他のソフト等で扱う場合は「0」にしてください。
$csv_data_esc = 1;

//CSVに保存する項目を指定する（保存したい項目のname属性の値を指定してください） ※空（指定しない）の場合（デフォルト）、全送信項目を保存します
//値はシングルクォーテーションで囲み、複数の場合はカンマで区切ってください （無効化する場合は「$regData = array();」として下さい）
//記述例　 $regData = array('ご用件','お名前','電話番号','Email','性別','サイトを知ったきっかけ','お問い合わせ内容');
// チェックボックス、ラジオボタンがある場合で、未入力の場合には空データを入れ、列ズレを防ぎたい場合に最適です（チェックボックスの場合 name="○○[]" の○○のみ指定下さい）
$regData = array();

//CSVダウンロードにセッション（ログイン）認証を利用する(する=1, しない=0) 
//自身でBasic認証を使用したい場合やFTPソフトでダウンロードする場合は「しない」にしてください。
//ダウンロード用URLは「サイトURL/mail.php?mode=download」と指定して下さい。認証画面が出ればOKです
$session_auth = 0;

//上記で認証を利用する場合の認証用ID、パスワード　（重要）必ず変更して下さい！
//半角英数字（なるべく複雑でかつ11文字以上で指定してください）
$userid   = 'admin';   // ユーザーID
$password = 'Thu85ad$lk2aC';   // パスワード

//----------------------------------------------------------------------
// CSV保存用設定 （END）
//----------------------------------------------------------------------


//----------------------------------------------------------------------
// スパムチェック用設定 （START）
//----------------------------------------------------------------------


//スパムチェックを行うかどうか(行う=1, 行わない=0)
$spamCheck = 0;

//禁止IPアドレス(文字列はシングルクーテーションで囲み、複数の場合はカンマで区切って下さい) 
//※完全一致によりチェックします。送信者のIPアドレスは受信メールに記述されています。
$ng_ip = array('000.000.00.1','000.000.00.2');

// 送信者IPを確認画面に表示したい場合にコメント解除
// echo $_SERVER["REMOTE_ADDR"];


//禁止ワード(文字列はシングルクーテーションで囲み、複数の場合はカンマで区切って下さい) ※URL、単語（英語、日本語）、htmlタグなどなんでも指定可能。
$ng_word = array('文字列を指定できます','文字列を指定できます','文字列を指定できます');

/* 
キーワード設定時の注意点
このキーワードを含んだものはすべて拒否されます。
たとえば「死」というキーワードを設定した場合、「死ぬ」、「死んだ」なども該当してしまいますのでご注意ください。
英語の場合も「kill」と設定した場合、「skill」も拒否されてしまいますのでキーワード設定には十分ご注意下さい。
*/


//禁止ワード検証を行うコメント欄（textarea）のname属性の値（空にすれば無効化できます）
$ng_word_name = "お問い合わせ内容";

//ローマ字の大文字、小文字を同一のものと判断する(する=1, しない=0)
$stri_check = 0;
//----------------------------------------------------------------------
// スパムチェック用設定 （END）
//----------------------------------------------------------------------


//----------------------------------------------------------------------
// メールアドレス2重チェック用設定 （START）
//----------------------------------------------------------------------
//※確認用メールアドレスは送信されるメールでは表示されません。（表示する必要性もないため）

//メールアドレス2重チェックする？(する=1, しない=0)
$mail_2check = 1;

//確認メールアドレス入力箇所のname属性の値（2重チェックに使用）
$ConfirmEmail = "Email（確認）";

//----------------------------------------------------------------------
// メールアドレス2重チェック用設定 （END）
//----------------------------------------------------------------------


//----------------------------------------------------------------------
//  添付ファイル処理用設定(BEGIN)
//----------------------------------------------------------------------
//確認画面表示の有り、無しの設定に関わらずファイル添付はご利用いただけます。（2021/10/26に改修）

/* ----- 重要 ------*/
//ファイルアップ部分のnameの値は必ず配列の形　例　upfile[]　としてください。※添付ファイルが1つでも
//添付ファイルは複数も可能です。

//例1 添付ファイルが1つの場合　
//添付ファイル <input type="file" name="upfile[]" />

//例2 添付ファイルが複数の場合　
//添付ファイル1：<input type="file" name="upfile[]" /> 添付ファイル2：<input type="file" name="upfile[]" />



//添付ファイルのMAXファイルサイズ　※単位バイト　デフォルトは5MB（ただしサーバーのphp.iniの設定による）
$maxImgSize = 5024000;

//添付ファイル一時保存用ディレクトリ ※書き込み可能なパーミッション（777等※サーバによる）にしてください
$tmp_dir_name = './tmp/';

//添付許可ファイル（拡張子）
//※大文字、小文字は区別されません（同じ扱い）のでここには小文字だけでOKです（拡張子を大文字で送信してもマッチします）
$permission_file = array('jpg','jpeg','gif','png','pdf','txt','xls','xlsx','zip','lzh','doc');

//フォームのファイル添付箇所のname属性の値 <input type="file" name="upfile[]" />の「upfile」部
$upfile_key = 'upfile';

//サーバー上の一時ファイルを削除する(する=1, しない=0)　※バックアップ目的で保存させておきたい場合など
//添付ファイルは確認画面表示時にtmpディレクトリに一旦保存されますが、それを送信時に削除するかどうか。（残す場合サーバー容量に余裕がある場合のみ推奨）
//もちろん手動での削除も可能です。
$tempFileDel = 1;//デフォルトは削除する

//確認画面→戻る→確認画面のページ遷移では最初の一時ファイルはサーバ上に残りますが、1時間後以降の最初の送信時に自動で削除されます。


//メールソフトで添付ファイル名が文字化けする場合には「1」にしてみてください。（ThuderBirdで日本語ファイル名文字化け対策）
//「1」にすると添付ファイル名が0～の連番になります。
$rename = 0;//(0 or 1)

//サーバーのphp.iniの「mail.add_x_header」がONかOFFかチェックを行う(する=1, しない=0)　※PHP5.3以降
//「する」場合、mail.add_x_headerがONの場合確認画面でメッセージが表示されます。
//mail.add_x_headerがONの場合、添付ファイルが正常に添付できない可能性があるためのチェックです。（問題がない場合も多いです）
//mail.add_x_headerはデフォルトは「OFF」ですが、サーバーによっては稀に「ON」になっているためです。
//mail.add_x_headerがONの場合でも正常に添付できていればこちらは「0」として下さい。メッセージは非表示となります。
$iniAddX = 0;

//添付ファイル名をCSVファイルにも記述する（↑のCSV保存機能がONの場合のみ有効）(する=1, しない=0)　
$attach2Csv = 0;

//----------------------------------------------------------------------
//  添付ファイル処理用設定(END)
//----------------------------------------------------------------------

//----------------------------------------------------------------------
//  メールアドレス振り分け用設定(START)
//----------------------------------------------------------------------

//振り分けを実施するする項目のname属性の値（name="○○"　の「○○」部分）
//プルダウン（<select>）、またはラジオボタン（input type="radio"）のみ有効です。
//無効にしたい場合には　$setName = "";　と空にして下さい。※デフォルトは無効化しています
$setName = "";

//お問い合わせ先とメールアドレスのセット（value属性の値とその送信先メールアドレスのセット）
//※左側がvalue属性の値（value="○○"の○○部分）、右側がそれに対応する送信先のメールアドレス
//追加、変更可能です。数は無制限です。行ごとに既存ルールに沿って記述下さい。（追加の際はコピペ下さい）
//メールアドレスを複数指定する場合は「,」で区切ってください （例 "aaa@aa.aa,bbb@bb.bb")
//変更、追加を行ったら都度送信テストを行うことを強くオススメします。
$name_address_array = array(
"A店舗" => "aaaa@xxx.com",
"B店舗" => "bbbb@xxx.com",
"C店舗" => "cccc@xxx.com",

);

//BCC振り分け送信先設定（あくまでも必要であればです）
//BCCは特に振り分け不要の場合には「$name_bcc_address_array = array();」として下さい。（67行目でBCC設定を行なっていればそちらには送信されます）
//ルールは上記と同じです　※要するにここの左側の文字列とセット数は上記と必ず同じになりますのでアドレス部分のみ変わることになります。（BCCのみ振り分けすることは不可となります）
$name_bcc_address_array = array(
"A店舗" => "bcc_aaaa@xxx.com",
"B店舗" => "bcc_bbbb@xxx.com",
"C店舗" => "bcc_cccc@xxx.com",

);

//----------------------------------------------------------------------
//  メールアドレス振り分け用設定(END)
//----------------------------------------------------------------------

//------------------------------- 任意設定ここまで ---------------------------------------------


// 以下の変更は知識のある方のみ自己責任でお願いします。


//----------------------------------------------------------------------
//  関数実行、変数初期化
//----------------------------------------------------------------------
//トークンチェック用のセッションスタート
if($useToken == 1 && $confirmDsp == 1){
	session_name('PHPMAILFORMSYSTEM');
	session_start();
}
$encode = "UTF-8";//このファイルの文字コード定義（変更不可）
//選択項目によるメールアドレスのセット
if( isset($_POST[$setName]) && array_key_exists($_POST[$setName], $name_address_array) ){
	$to = $name_address_array[$_POST[$setName]];
	$BccMail = (isset($name_bcc_address_array[$_POST[$setName]])) ? $name_bcc_address_array[$_POST[$setName]] : $BccMail;
}

if(isset($_GET)) $_GET = sanitize($_GET);//NULLバイト除去//
if(isset($_POST)) $_POST = sanitize($_POST);//NULLバイト除去//
if(isset($_COOKIE)) $_COOKIE = sanitize($_COOKIE);//NULLバイト除去//
//----------------------------------------------------------------------
//  CSVダウンロード認証とダイアログ表示(START)
//----------------------------------------------------------------------
if(!empty($_GET['mode']) && $_GET['mode'] == 'download' && $session_auth == 0){ exit(); }
if(!empty($_GET['mode']) && $_GET['mode'] == 'download' && $session_auth == 1){
	csvDialog($csv_file_path,$userid,$password);
}
//----------------------------------------------------------------------
//  CSVダウンロード認証とダイアログ表示(END)
//----------------------------------------------------------------------
if($encode == 'SJIS') $_POST = sjisReplace($_POST,$encode);//Shift-JISの場合に誤変換文字の置換実行
$funcRefererCheck = refererCheck($Referer_check,$Referer_check_domain);//リファラチェック実行

//変数初期化
$sendmail = 0;
$empty_flag = 0;
$post_mail = '';
$errm ='';
$header ='';

//----------------------------------------------------------------------
//  CSV保存ディレクトリパーミッションチェック(BEGIN)
//----------------------------------------------------------------------
if($csv_backup == 1 && (!file_exists($csv_dir) || !is_writable($csv_dir))){
	exit('（重大なエラー）CSV保存用のディレクトリが無いかパーミッションが正しくありません。$csv_dirで指定してるディレクトリが存在するか、または$csv_dirで指定してるディレクトリのパーミッションを書き込み可能（777等※サーバによる）にしてください');	
}
//----------------------------------------------------------------------
//  CSV保存ディレクトリパーミッションチェック(END)
//----------------------------------------------------------------------

//----------------------------------------------------------------------
//  添付ファイル処理(BEGIN)
//----------------------------------------------------------------------

if(isset($_FILES[$upfile_key])){
	$file_count = count($_FILES[$upfile_key]["tmp_name"]);
	for ($i=0;$i<$file_count;$i++) {
	
		if (@is_uploaded_file($_FILES[$upfile_key]["tmp_name"][$i])) {
			if ($_FILES[$upfile_key]["size"][$i] < $maxImgSize) {
				
				//許可拡張子チェック
				$upfile_name_check = '';
				$upfile_name_array[$i] = explode('.',$_FILES[$upfile_key]['name'][$i]);
				$upfile_name_array_extension[$i] = strtolower(end($upfile_name_array[$i]));
				foreach($permission_file as $permission_val){
				  if($upfile_name_array_extension[$i] == $permission_val){
					  $upfile_name_check = 'checkOK';
				  }
				}
				if($upfile_name_check != 'checkOK'){
				  $errm .= "<p class=\"error_messe\">「".$_FILES[$upfile_key]['name'][$i]."」は許可されていない拡張子です。</p>\n";
				  $empty_flag = 1;
				}else{
				
					  $temp_file_name[$i] = $_FILES[$upfile_key]["name"][$i];
					  $temp_file_name_array[$i] =  explode('.',$temp_file_name[$i]);
					  
					  if(count($temp_file_name_array[$i]) < 2){
						$errm .= "<p class=\"error_messe\">ファイルに拡張子がありません。</p>\n";
						$empty_flag = 1;
					  }else{
						$extension = end($temp_file_name_array[$i]);
						
						  if(function_exists('uniqid')){
							  if(!file_exists($tmp_dir_name) || !is_writable($tmp_dir_name)){
							  exit("（重大なエラー）添付ファイル一時保存用のディレクトリが無いかパーミッションが正しくありません。{$tmp_dir_name}ディレクトリが存在するか、または{$tmp_dir_name}ディレクトリのパーミッションを書き込み可能（777等※サーバによる）にしてください");	
							  }
						  $upFileName[$i] = uniqid('temp_file_').mt_rand(10000,99999).'.'.$extension;
						  $upFilePath[$i] = $tmp_dir_name.$upFileName[$i];
						  
						  }else{
							  exit('（重大なエラー）添付ﾌｧｲﾙ一時ﾌｧｲﾙ用のﾕﾆｰｸIDを生成するuniqid関数が存在しません。<br>PHPのﾊﾞｰｼﾞｮﾝが極端に低い（PHP4未満）ようです。<br>PHPをﾊﾞｰｼﾞｮﾝｱｯﾌﾟするか配布元に相談ください');	
						  }
						  move_uploaded_file($_FILES[$upfile_key]['tmp_name'][$i],$upFilePath[$i]);
						  @chmod($upFilePath[$i], 0666);
						  
						  //確認画面なしの場合はこの時点で添付ファイル情報をPOSTにセットする
						  if($confirmDsp == 0){
							  $_POST['upfilePath'][] = h($upFilePath[$i]);
							  $_POST['upfileType'][] = h($_FILES[$upfile_key]['type'][$i]);
							  $_POST['upfileOriginName'][] = h($_FILES[$upfile_key]['name'][$i]);
						  }
						  
					  }
				}
			}else{
				  $errm .= "<p class=\"error_messe\">「".$_FILES[$upfile_key]['name'][$i]."」はファイルサイズが大きすぎます。</p>\n";
				  $empty_flag = 1;
			}
		}
	}
}
//----------------------------------------------------------------------
//  添付ファイル処理(END)
//----------------------------------------------------------------------

// 禁止IP,スパムチェック
if($spamCheck == 1){
	$spamCheckRes = spamCheck($ng_ip,$ng_word_name,$ng_word,$stri_check);
	$errm .= $spamCheckRes['errm'];
	if($spamCheckRes['empty_flag'] == 1) $empty_flag = $spamCheckRes['empty_flag'];
}
if($requireCheck == 1) {
	$requireResArray = requireCheck($require);//必須チェック実行し返り値を受け取る
	$errm .= $requireResArray['errm'];
	if($requireResArray['empty_flag'] == 1) $empty_flag = $requireResArray['empty_flag'];
}
//メールアドレスチェック
if(empty($errm)){
	foreach($_POST as $key=>$val) {
		if($val == "confirm_submit") $sendmail = 1;
		if($key == $Email) $post_mail = h($val);
		if($key == $Email && $mail_check == 1 && !empty($val)){
			if(!checkMail($val)){
				$errm .= "<p class=\"error_messe\">【".$key."】はメールアドレスの形式が正しくありません。</p>\n";
				$empty_flag = 1;
			}
		}
		//メール2重チェック用確認メールアドレス取得
		if($key == $ConfirmEmail){
			$post_mail2 = h($val);
		}
	}
	//----------------------------------------------------------------------
	//  メール2重チェック(BEGIN)
	//----------------------------------------------------------------------
	if(!empty($post_mail) && !empty($post_mail2) && $post_mail != $post_mail2 && $mail_2check == 1){
			  $errm .= "<p class=\"error_messe\">確認メールアドレスが一致しません。</p>\n";
			  $empty_flag = 1;
	}
	//----------------------------------------------------------------------
	//  メール2重チェック(BEGIN)
	//----------------------------------------------------------------------
}

if(($confirmDsp == 0 || $sendmail == 1) && $empty_flag != 1){
	
	//トークンチェック（CSRF対策）※確認画面がONの場合のみ実施
	if($useToken == 1 && $confirmDsp == 1){
		if(empty($_SESSION['mailform_token']) || ($_SESSION['mailform_token'] !== $_POST['mailform_token'])){
			exit('ページ遷移が不正です');
		}
		if(isset($_SESSION['mailform_token'])) unset($_SESSION['mailform_token']);//トークン破棄
		if(isset($_POST['mailform_token'])) unset($_POST['mailform_token']);//トークン破棄
	}
	
	//差出人に届くメールをセット
	if($remail == 1) {
		$userBody = mailToUser($_POST,$dsp_name,$remail_text,$mailFooterDsp,$mailSignature,$encode);
		$reheader = userHeader($refrom_name,$from,$encode);
		$re_subject = "=?iso-2022-jp?B?".base64_encode(mb_convert_encoding($re_subject,"JIS",$encode))."?=";
	}
	//管理者宛に届くメールをセット
	$adminBody = mailToAdmin($_POST,$subject,$mailFooterDsp,$mailSignature,$encode,$confirmDsp);
	$header = adminHeader($post_mail,$BccMail);
	  
	//トラバーサルチェック
	if(isset($_POST['upfilePath'])){
		traversalCheck($tmp_dir_name);
	}
	
	//-fオプションによるエンベロープFrom（Return-Path）の設定(safe_modeがOFFの場合かつ上記設定がONの場合のみ実施)
	if($use_envelope == 0){
		$result = mb_send_mail($to,$subject,$adminBody,$header);
		if($remail == 1 && !empty($post_mail)) mail($post_mail,$re_subject,$userBody,$reheader);
	}else{
		$result = mb_send_mail($to,$subject,$adminBody,$header,'-f'. $from);
		if($remail == 1 && !empty($post_mail)) mail($post_mail,$re_subject,$userBody,$reheader,'-f'. $from);
	}
	
	//サーバ上の一時ファイルを削除
	$dir = rtrim($tmp_dir_name,'/');
	deleteFile($dir,$tempFileDel);
	
  	//CSVバックアップ処理
	if($csv_backup == 1){
		csvBackup($csv_file_path,$csv_data_esc,$regData);
	}
}
else if($confirmDsp == 1){ 

/*　▼▼▼送信確認画面のレイアウト※編集可　オリジナルのデザインも適用可能▼▼▼　*/
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>お問い合わせ | こゆるり神社</title>
  <meta name="description" content="こゆるり神社（神奈川県三浦市三崎）のご案内。御祭神、由来、授与品、所在地、お問い合わせ。">

  <!-- Fonts（参考ページの雰囲気：Noto Serif JP + Zen Maru Gothic） -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Noto+Serif+JP:wght@400;500;600;700&family=Zen+Maru+Gothic:wght@400;500;700&display=swap"
    rel="stylesheet"
  >
    <link rel="stylesheet" href="./style.css">

</head>

<body>

  <header class="site-header">
    <div class="wrap">
      <div class="header-inner">
        <a href="index.html" class="brand" aria-label="こゆるり神社">
          <div class="brand-logo">
            <img src="./images/logo.png" alt="">
          </div>
          <div class="brand-title">こゆるり神社</div>
        </a>

        <p class="header-catch">小さな神社の大きなご利益</p>

        <div class="header-actions">
          <a class="icon-btn" href="https://shop.koyururi-jinja.com/" target="_blank" rel="noopener" aria-label="授与品へ">
            <img src="./images/shop.png" alt="">
          </a>

          <button
            class="icon-btn menu-btn"
            type="button"
            aria-label="メニューを開く"
            aria-controls="spNav"
            aria-expanded="false"
            data-open-src="./images/open.png"
            data-close-src="./images/close.png"
          >
            <img src="./images/open.png" alt="">
          </button>
        </div>
      </div>
    </div>
  </header>

  <div class="wrap">
    <section class="fv" aria-label="ファーストビュー">
      <div class="fv-hero">
        <!-- トップFV：画像表示（動画に切り替える場合は以下をコメントアウトし、下の動画コードを有効化） -->
        <img class="fv-image" src="./images/hero.png" alt="こゆるり神社 小さな神社の大きなご利益">
        <!-- トップFV：動画表示 -->
        <!--
        <video class="fv-video" src="./images/hero.mp4" autoplay muted playsinline></video>
        -->
      </div>

      <nav class="pills" aria-label="ページ内メニュー">
        <a class="pill" href="yurai.html">由来</a>
        <a class="pill" href="gosaijin.html">御祭神</a>
        <a class="pill" href="gokitou.html">ご祈祷</a>
        <a class="pill" href="onayami.html">お悩み相談</a>
      </nav>
<!-- 
      <div class="intro prose">
        <small>神奈川県三浦市三崎</small>
        <small>こゆるり神社</small>
        <small>
          三崎の神社を本宮とし、神奈川県大和市に小さな神社を作り、お祀りしたいと思っております。
        </small>
      </div>
    </section> -->

    <main class="prose">

      <!-- お問い合わせ -->
      <section id="contact" class="section section--ref">
        <h1 class="ref-title">お問い合わせ</h1>

<!-- ▲ Headerやその他コンテンツなど　※自由に編集可 ▲-->

<!-- ▼************ 送信内容表示部　※編集は自己責任で ************ ▼-->
<div id="formWrap">
<?php if($empty_flag == 1){ ?>
<div align="center">
<h2>入力にエラーがあります。下記をご確認の上「戻る」ボタンにて修正をお願い致します。</h2>
<?php echo $errm; ?><br /><br /><input type="button" value=" 前画面に戻る " onclick="history.back()">
</div>
<?php }else{ ?>
<h2>確認画面</h2>
<p align="center">以下の内容で間違いがなければ、「送信する」ボタンを押してください。</p>
<?php iniGetAddMailXHeader($iniAddX);//php.ini設定チェック?>
<form action="<?php echo h($_SERVER['SCRIPT_NAME']); ?>" method="POST">
<table class="formTable">
<?php echo confirmOutput($_POST);//入力内容を表示?>
</table>
<p align="center"><input type="hidden" name="mail_set" value="confirm_submit">
<input type="hidden" name="httpReferer" value="<?php echo h($_SERVER['HTTP_REFERER']) ;?>">
<?php
if(isset($_FILES[$upfile_key]["tmp_name"])){
	$file_count = count($_FILES[$upfile_key]["tmp_name"]);
	for ($i=0;$i<$file_count;$i++) {
		if(!empty($_FILES[$upfile_key]["tmp_name"][$i])){
?>
<input type="hidden" name="upfilePath[]" value="<?php echo h($upFilePath[$i]);?>">
<input type="hidden" name="upfileType[]" value="<?php echo h($_FILES[$upfile_key]['type'][$i]);?>">
<input type="hidden" name="upfileOriginName[]" value="<?php echo h($_FILES[$upfile_key]['name'][$i]);?>">
<?php 
		}
	}
}
?>
<input type="button" value="前画面に戻る" onclick="history.back()">
<input type="submit" value="　送信する　"></p>
</form>
<?php copyright();} ?>
</div><!-- /formWrap -->
<!-- ▲ *********** 送信内容確認部　※編集は自己責任で ************ ▲-->

<!-- ▼ Footerその他コンテンツなど　※編集可 ▼-->
      </section>


    </main>
    <footer class="prose">
      <!-- 
      <div class="footer-sns">
        <a href="https://instagram.com/" target="_blank" rel="noopener" aria-label="Instagram">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
        </a>
        <a href="https://facebook.com/" target="_blank" rel="noopener" aria-label="Facebook">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/></svg>
        </a>
        <a href="https://x.com/" target="_blank" rel="noopener" aria-label="X">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
        </a>
      </div>
       -->
      <address>こゆるり神社<br>
        〒238-0243 神奈川県三浦市三崎1丁目10-10<br>
        お問い合わせ：<a href="mailto:contact@koyururi-jinja.com">contact@koyururi-jinja.com</a></address>

      <div class="footer-links" aria-label="フッターリンク">
        <a href="tos.html" aria-label="利用規約">利用規約</a>
        <span class="sep" aria-hidden="true">|</span>
        <a href="privacy.html" aria-label="プライバシー・ポリシー">プライバシー・ポリシー</a>
        <span class="sep" aria-hidden="true">|</span>
        <a href="required-information.html" aria-label="特定商取引法に基づく表示">特定商取引法に基づく表示</a>
      </div>

      <div style="margin-top:8px;">&copy; 2026 こゆるり神社 All Rights Reserved.</div>
    </footer>

    <aside id="spNav" class="sp-nav" aria-hidden="true">
      <div class="sp-panel" role="dialog" aria-modal="true" aria-label="メニュー">
        <div class="sp-head">
          <p class="sp-title">小さな神社の大きな御利益</p>
          <button class="icon-btn sp-close" type="button" aria-label="メニューを閉じる">
            <img src="./images/close.png" alt="">
          </button>
        </div>
        <div class="brand" aria-label="こゆるり神社">
          <div class="brand-logo">
            <img src="./images/logo.png" alt="">
          </div>
          <div class="brand-title">こゆるり神社</div>
        </div>
        <ul class="sp-menu">
          <li><a href="index.html">小さな神社の大きなご利益</a></li>
          <li><a href="yurai.html">由来</a></li>
          <li><a href="gosaijin.html">御祭神</a></li>
          <li><a href="gokitou.html">ご祈祷</a></li>
          <li><a href="https://shop.koyururi-jinja.com/" target="_blank" rel="noopener">授与品</a></li>
          <li><a href="index.html#goods">写真</a></li>
          <li><a href="contact.html">お問い合わせ</a></li>
        </ul>
      </div>
    </aside>

  </div>

  <!-- 固定CTA（PC：右縦） -->
  <nav class="fixed-cta fixed-cta--pc" aria-label="固定ボタン（PC）">
    <a class="cta-contact" href="contact.html" aria-label="お問い合わせへ">お問い合わせ</a>
    <a class="cta-goods" href="https://shop.koyururi-jinja.com/" target="_blank" rel="noopener" aria-label="授与品へ">授与品</a>
  </nav>

  <!-- 固定CTA（SP：下横） -->
  <nav class="fixed-cta fixed-cta--sp" aria-label="固定ボタン（SP）">
    <div class="cta-row">
      <a class="cta-contact" href="contact.html" aria-label="お問い合わせへ">お問い合わせ</a>
      <a class="cta-goods" href="https://shop.koyururi-jinja.com/" target="_blank" rel="noopener" aria-label="授与品へ">授与品</a>
    </div>
  </nav>

  <!-- 上に戻る -->
  <button id="backToTop" class="back-to-top" type="button" aria-label="ページ上部へ戻る">↑</button>
  <script src="./script.js" defer></script>
</body>
</html>
<?php
/* ▲▲▲送信確認画面のレイアウト　※オリジナルのデザインも適用可能▲▲▲　*/
}

if(($jumpPage == 0 && $sendmail == 1) || ($jumpPage == 0 && ($confirmDsp == 0 && $sendmail == 0))) { 

/* ▼▼▼送信完了画面のレイアウト　編集可 ※送信完了後に指定のページに移動しない場合のみ表示▼▼▼　*/
?>
<!DOCTYPE HTML>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
<meta name="format-detection" content="telephone=no">
<title>完了画面</title>
</head>
<body>
<div align="center">
<?php if($empty_flag == 1){ ?>
<h2>入力にエラーがあります。下記をご確認の上「戻る」ボタンにて修正をお願い致します。</h2>
<div style="color:red"><?php echo $errm; ?></div>
<br /><br /><input type="button" value=" 前画面に戻る " onclick="history.back()">
</div>
</body>
</html>
<?php }else{ ?>
送信ありがとうございました。<br />
送信は正常に完了しました。<br /><br />
<a href="<?php echo $site_top ;?>">トップページへ戻る&raquo;</a>
</div>
<?php copyright(); ?>
<!--  CV率を計測する場合ここにAnalyticsコードを貼り付け -->
</body>
</html>
<?php 
/* ▲▲▲送信完了画面のレイアウト 編集可 ※送信完了後に指定のページに移動しない場合のみ表示▲▲▲　*/
  }
}
//確認画面無しの場合の表示、指定のページに移動する設定の場合、エラーチェックで問題が無ければ指定ページヘリダイレクト
else if(($jumpPage == 1 && $sendmail == 1) || $confirmDsp == 0) { 
	if($empty_flag == 1){ ?>
<div align="center"><h2>入力にエラーがあります。下記をご確認の上「戻る」ボタンにて修正をお願い致します。</h2><div style="color:red"><?php echo $errm; ?></div><br /><br /><input type="button" value=" 前画面に戻る " onclick="history.back()"></div>
<?php 
	}else{ header("Location: ".$thanksPage); }
}

// 以下の変更は知識のある方のみ自己責任でお願いします。

//----------------------------------------------------------------------
//  関数定義(START)
//----------------------------------------------------------------------
function checkMail($str){
	$mailaddress_array = explode('@',$str);
	if(preg_match("/^[\.!#%&\-_0-9a-zA-Z\?\/\+]+\@[!#%&\-_0-9a-zA-Z]+(\.[!#%&\-_0-9a-zA-Z]+)+$/", "$str") && count($mailaddress_array) ==2){
		return true;
	}else{
		return false;
	}
}
function h($string) {
	global $encode;
	return htmlspecialchars($string, ENT_QUOTES,$encode);
}
function sanitize($arr){
	if(is_array($arr)){
		return array_map('sanitize',$arr);
	}
	return str_replace("\0","",$arr);
}
//Shift-JISの場合に誤変換文字の置換関数
function sjisReplace($arr,$encode){
	foreach($arr as $key => $val){
		$key = str_replace('＼','ー',$key);
		$resArray[$key] = $val;
	}
	return $resArray;
}
//送信メールにPOSTデータをセットする関数
function postToMail($arr){
	global $hankaku,$hankaku_array,$ConfirmEmail;
	$resArray = '';
	foreach($arr as $key => $val){
		$out = '';
		if(is_array($val)){
			foreach($val as $key02 => $item){ 
				//連結項目の処理
				if(is_array($item)){
					$out .= connect2val($item);
				}else{
					$out .= $item . ', ';
				}
			}
			$out = rtrim($out,', ');
			
		}else{ $out = $val; }//チェックボックス（配列）追記ここまで
		
		if (version_compare(PHP_VERSION, '5.1.0', '<=')) {//PHP5.1.0以下の場合のみ実行（7.4でget_magic_quotes_gpcが非推奨になったため）
			if(get_magic_quotes_gpc()) { $out = stripslashes($out); }
		}
		
		
		//全角→半角変換
		if($hankaku == 1){
			$out = zenkaku2hankaku($key,$out,$hankaku_array);
		}
		
		if($out != "confirm_submit" && $key != "httpReferer" && $key != "upfilePath" && $key != "upfileType" && $key != $ConfirmEmail) {
			
			if($key == "upfileOriginName" && $out !=''){
				$key = '添付ファイル';
			}elseif($key == "upfileOriginName" && $out ==''){
				continue;
			}
			
			$resArray .= "【 ".$key." 】 ".$out."\n";
		}
	}
	return $resArray;
}
//確認画面の入力内容出力用関数
function confirmOutput($arr){
	global $upFilePath,$upfile_key,$encode,$hankaku,$hankaku_array,$useToken,$confirmDsp,$replaceStr;
	$html = '';
	foreach($arr as $key => $val) {
		$out = '';
		if(is_array($val)){
			foreach($val as $key02 => $item){ 
				//連結項目の処理
				if(is_array($item)){
					$out .= connect2val($item);
				}else{
					$out .= $item . ', ';
				}
			}
			$out = rtrim($out,', ');
			
		}else{ $out = $val; }//チェックボックス（配列）追記ここまで
		
		if (version_compare(PHP_VERSION, '5.1.0', '<=')) {//PHP5.1.0以下の場合のみ実行（7.4でget_magic_quotes_gpcが非推奨になったため）
			if(get_magic_quotes_gpc()) { $out = stripslashes($out); }
		}
		
		//全角→半角変換
		if($hankaku == 1){
			$out = zenkaku2hankaku($key,$out,$hankaku_array);
		}
		
		$out = nl2br(h($out));//※追記 改行コードを<br>タグに変換
		$key = h($key);
		$out = str_replace($replaceStr['before'], $replaceStr['after'], $out);//機種依存文字の置換処理
		
		$html .= "<tr><th>".$key."</th><td>".mb_convert_kana($out,"K", $encode);
		$html .= '<input type="hidden" name="'.$key.'" value="'.str_replace(array("<br />","<br>"),"",mb_convert_kana($out,"K", $encode)).'" />';
		$html .= "</td></tr>\n";
		
	}
	
	//添付ファイル表示処理
	if(isset($_FILES[$upfile_key]["tmp_name"])){
		$file_count = count($_FILES[$upfile_key]["tmp_name"]);
		$j = 1;
		for($i=0;$i<$file_count;$i++,$j++) {
			//添付があったらファイル名表示
			if(!empty($upFilePath[$i])){
			  $html .= "<tr><th>添付ファイル名{$j}.</th><td>{$_FILES[$upfile_key]['name'][$i]}</td></tr>\n";
			}
		}
	}
	
	//トークンをセット
	if($useToken == 1 && $confirmDsp == 1){
		$token = sha1(uniqid(mt_rand(), true));
		$_SESSION['mailform_token'] = $token;
		$html .= '<input type="hidden" name="mailform_token" value="'.$token.'" />';
	}
	
	return $html;
}
//全角→半角変換
function zenkaku2hankaku($key,$out,$hankaku_array){
	global $encode;
	if(is_array($hankaku_array) && function_exists('mb_convert_kana')){
		foreach($hankaku_array as $hankaku_array_val){
			if($key == $hankaku_array_val){
				$out = mb_convert_kana($out,'a',$encode);
			}
		}
	}
	return $out;
}
//配列連結の処理
function connect2val($arr){
	$out = '';
	foreach($arr as $key => $val){
		if($key === 0 || $val == ''){//配列が未記入（0）、または内容が空のの場合には連結文字を付加しない（型まで調べる必要あり）
			$key = '';
		}elseif(strpos($key,"円") !== false && $val != '' && preg_match("/^[0-9]+$/",$val)){
			$val = number_format($val);//金額の場合には3桁ごとにカンマを追加
		}
		$out .= $val . $key;
	}
	return $out;
}
//管理者宛送信メールヘッダ
function adminHeader($post_mail,$BccMail){
	global $encode,$from,$from_add;
	
	//メールで日本語使用するための設定
	mb_language("Ja") ;
	mb_internal_encoding($encode);
	
	$header="From: ";
	if(!empty($post_mail) && $from_add == 1){
		$header .= mb_encode_mimeheader('"'.$post_mail.'"')." <".$from.">\n";
	}else{
		$header .= $from."\n";
	}
	if(!empty($BccMail)) {
		$header.="Bcc: $BccMail\n";
	}
	if(!empty($post_mail)) {
		$header.="Reply-To: ".$post_mail."\n";
	}
	
	//----------------------------------------------------------------------
	//  添付ファイル処理(START)
	//----------------------------------------------------------------------
	if(isset($_POST['upfilePath'])){
		$header .= "MIME-Version: 1.0\n";
		$header .= "Content-Type: multipart/mixed; boundary=\"__PHPFACTORY__\"\n";
	}else{
		$header.="Content-Type:text/plain;charset=iso-2022-jp\nX-Mailer: PHP/".phpversion();
	}
	
	return $header;
}
//管理者宛送信メールボディ
function mailToAdmin($arr,$subject,$mailFooterDsp,$mailSignature,$encode,$confirmDsp){
	global $rename;
	$adminBody = '';
	//----------------------------------------------------------------------
	//  添付ファイル処理(START)
	//----------------------------------------------------------------------
	if(isset($_POST['upfilePath'])){
		$adminBody .= "--__PHPFACTORY__\n";
		$adminBody .= "Content-Type: text/plain; charset=\"ISO-2022-JP\"\n";
		$adminBody .= "\n";
	}
	//----------------------------------------------------------------------
	//  添付ファイル処理(END)
	//----------------------------------------------------------------------
	
	$adminBody .="「".$subject."」からメールが届きました\n\n";
	$adminBody .="＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝\n\n";
	$adminBody .= postToMail($arr);//POSTデータを関数からセット
	$adminBody .="\n＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝\n";
	$adminBody .="送信された日時：".date( "Y/m/d (D) H:i:s", time() )."\n";
	$adminBody .="送信者のIPアドレス：".@$_SERVER["REMOTE_ADDR"]."\n";
	$adminBody .="送信者のホスト名：".getHostByAddr(getenv('REMOTE_ADDR'))."\n";
	if($confirmDsp != 1){
		$adminBody.="問い合わせのページURL：".@h($_SERVER['HTTP_REFERER'])."\n";
	}else{
		$adminBody.="問い合わせのページURL：".@$arr['httpReferer']."\n";
	}
	if($mailFooterDsp == 1) $adminBody.= $mailSignature."\n";
	
//----------------------------------------------------------------------
//  添付ファイル処理(START)
//----------------------------------------------------------------------

if(isset($_POST['upfilePath'])){
	
	$default_internal_encode = mb_internal_encoding();
	if($default_internal_encode != $encode){
		mb_internal_encoding($encode);
	}

	$file_count = count($_POST['upfilePath']);
											 
	for ($i=0;$i<$file_count;$i++) {
	
		if(isset($_POST['upfilePath'][$i])){
		
		$adminBody .= "--__PHPFACTORY__\n";
		$filePath = h(@$_POST['upfilePath'][$i]);//ファイルパスを指定
		$fileName = h(mb_encode_mimeheader(@$_POST['upfileOriginName'][$i]));
		$imgType = h(@$_POST['upfileType'][$i]);
		
		//ファイル名が文字化けする場合には連番ファイル名とする
		if($rename == 1){
			$fileNameArray = explode(".",$fileName);
			$fileName = $i.'.'.end($fileNameArray);
		}
		
		
		# 添付ファイルへの処理をします。
		$handle = @fopen($filePath, 'r');
		$attachFile = @fread($handle, filesize($filePath));
		@fclose($handle);
		$attachEncode = base64_encode($attachFile);
		
		$adminBody .= "Content-Type: {$imgType}; name=\"$filePath\"\n";
		$adminBody .= "Content-Transfer-Encoding: base64\n";
		$adminBody .= "Content-Disposition: attachment; filename=\"$fileName\"\n";
		$adminBody .= "\n";
		$adminBody .= chunk_split($attachEncode) . "\n";
		}
	}
		$adminBody .= "--__PHPFACTORY__--\n";
}
//----------------------------------------------------------------------
//  添付ファイル処理(END)
//----------------------------------------------------------------------
	
	//return mb_convert_encoding($adminBody,"JIS",$encode);
	return $adminBody;
}

//ユーザ宛送信メールヘッダ
function userHeader($refrom_name,$to,$encode){
	$reheader = "From: ";
	if(!empty($refrom_name)){
		$default_internal_encode = mb_internal_encoding();
		if($default_internal_encode != $encode){
			mb_internal_encoding($encode);
		}
		$reheader .= mb_encode_mimeheader($refrom_name)." <".$to.">\nReply-To: ".$to;
	}else{
		$reheader .= "$to\nReply-To: ".$to;
	}
	$reheader .= "\nContent-Type: text/plain;charset=iso-2022-jp\nX-Mailer: PHP/".phpversion();
	return $reheader;
}
//ユーザ宛送信メールボディ
function mailToUser($arr,$dsp_name,$remail_text,$mailFooterDsp,$mailSignature,$encode){
	$userBody = '';
	if(isset($arr[$dsp_name])) $userBody = h($arr[$dsp_name]). " 様\n";
	$userBody.= $remail_text;
	$userBody.="\n＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝\n\n";
	$userBody.= postToMail($arr);//POSTデータを関数からセット
	$userBody.="\n＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝\n\n";
	$userBody.="送信日時：".date( "Y/m/d (D) H:i:s", time() )."\n";
	if($mailFooterDsp == 1) $userBody.= $mailSignature;
	return mb_convert_encoding($userBody,"JIS",$encode);
}
//必須チェック関数
function requireCheck($require){
	$res['errm'] = '';
	$res['empty_flag'] = 0;
	foreach($require as $requireVal){
		$existsFalg = '';
		foreach($_POST as $key => $val) {
			if($key == $requireVal) {
				
				//連結指定の項目（配列）のための必須チェック
				if(is_array($val)){
					$connectEmpty = 0;
					foreach($val as $kk => $vv){
						if(is_array($vv)){
							foreach($vv as $kk02 => $vv02){
								if($vv02 == ''){
									$connectEmpty++;
								}
							}
						}
						
					}
					if($connectEmpty > 0){
						$res['errm'] .= "<p class=\"error_messe\">【".h($key)."】は必須項目です。</p>\n";
						$res['empty_flag'] = 1;
					}
				}
				//デフォルト必須チェック
				elseif($val == ''){
					$res['errm'] .= "<p class=\"error_messe\">【".h($key)."】は必須項目です。</p>\n";
					$res['empty_flag'] = 1;
				}
				
				$existsFalg = 1;
				break;
			}
			
		}
		if($existsFalg != 1){
				$res['errm'] .= "<p class=\"error_messe\">【".$requireVal."】が未選択です。</p>\n";
				$res['empty_flag'] = 1;
		}
	}
	
	return $res;
}
//リファラチェック
function refererCheck($Referer_check,$Referer_check_domain){
	if($Referer_check == 1 && !empty($Referer_check_domain)){
		if(strpos(h($_SERVER['HTTP_REFERER']),$Referer_check_domain) === false){
			return exit('<p align="center">リファラチェックエラー。フォームページのドメインとこのファイルのドメインが一致しません</p>');
		}
	}
}
function copyright(){
	//echo '<a style="display:block;text-align:center;margin:15px 0;font-size:11px;color:#aaa;text-decoration:none" href="http://www.php-factory.net/" target="_blank" rel="noopener">- PHP工房 -</a>';
}
//ファイル添付用一時ファイルの削除
function deleteFile($dir,$tempFileDel){
	global $permission_file;
	
	if($tempFileDel == 1){
		if(isset($_POST['upfilePath'])){
			foreach($_POST['upfilePath'] as $key => $val){
				
				foreach($permission_file as $permission_file_val){
					if(strpos(strtolower($val),$permission_file_val) !== false && file_exists($val)){
						if(strpos($val,'htaccess') !== false) exit();
						unlink($val);
						break;
					}
				}
					
			}
		}
		
		//ゴミファイルの削除（1時間経過したもののみ）※確認画面→戻る→確認画面の場合、先の一時ファイルが残るため
		if(file_exists($dir) && !empty($dir)){
		$handle = opendir($dir);
		  while($temp_filename = readdir($handle)){
			if(strpos($temp_filename,'temp_file_') !== false ){
				if( strtotime(date("Y-m-d H:i:s",filemtime($dir."/".$temp_filename))) < strtotime(date("Y-m-d H:i:s",strtotime("-1 hour"))) ){
					@unlink("$dir/$temp_filename");
				}
			}
		  }
		}
	}
}	
//php.iniのmail.add_x_headerのチェック
function iniGetAddMailXHeader($iniAddX){
	if($iniAddX == 1){ 
		if(@ini_get('mail.add_x_header') == 1) echo '<p style="color:red">php.iniの「mail.add_x_header」がONになっています。添付がうまくいかない可能性が高いです。htaccessファイルかphp.iniファイルで設定を変更してOFFに設定下さい。サーバーにより設定方法は異なります。詳しくはサーバーマニュアル等、またはサーバー会社にお問い合わせ下さい。正常に添付できていればOKです。このメーッセージはmail.php内のオプションで非表示可能です</p>'; 
	}
}

//トラバーサル対策
function traversalCheck($tmp_dir_name){
	if(isset($_POST['upfilePath']) && is_array($_POST['upfilePath'])){
		foreach($_POST['upfilePath'] as $val){
			if(strpos($val,$tmp_dir_name) === false || strpos($val,'temp_file_') === false) exit('Warning!! you are wrong..1');//ルール違反は強制終了
			if(substr_count($tmp_dir_name,'/') != substr_count($val,'/') ) exit('Warning!! you are wrong..2');//ルール違反は強制終了
			if(strpos($val,'htaccess') !== false) exit('Warning!! you are wrong..3');
			if(!file_exists($val)) exit('Warning!! you are wrong..4');
			if(strpos(str_replace($tmp_dir_name,'',$val),'..') !== false)  exit('Warning!! you are wrong..5');
		}
	}
}
//文字列をCSV出力形式に変換
function csv_string($str){
	global $encode;
	$csv_data = $str;
	$csv_data = str_replace('"','""',$csv_data);
	$csv_data = str_replace(',','、',$csv_data);
	return '"'.mb_convert_encoding($csv_data, "sjis-win", $encode).'"';
}
//CSV生成と登録
function csvBackup($csv_file_path,$csv_data_esc,$regData){
	global $attach2Csv;
		$countRegData = count($regData);
		//----------------------------------------------------------------------
		//  CSVファイルの存在チェック(BEGIN)
		//----------------------------------------------------------------------
		//ファイルが存在しない場合にはヘッダーをつけてファイルを生成します
		if(!file_exists($csv_file_path)){
			
			$csv  = "";//初期値
			
			//登録データが指定されている場合の処理
			if($countRegData > 0){
				foreach($regData as $regDataVal){
					$csv .= csv_string($regDataVal).",";
				}
			}
			//登録データが指定されていない場合にはPOSTデータすべてを保存
			else{
				
				foreach($_POST as $key=>$val) {
					if($val != "confirm_submit" && $key != "httpReferer" && $key != "upfilePath" && $key != "upfileType" && $key != "upfileOriginName") {
							$csv .= csv_string($key).",";
					}
				}
			}
			
			$csv .= ($attach2Csv == 1) ? csv_string("添付ファイル名")."," : '';//添付ファイル（不要な場合削除可）
			$csv .= csv_string("問い合わせのページURL").",";//問い合わせのページURL（不要な場合削除可）
			$csv .= csv_string('問い合わせ日付').",";//申し込み日付（不要な場合削除可）
			$csv .= csv_string('IPアドレス').",";//IPアドレス（不要な場合削除可）
			
			$csv = rtrim($csv,",");
			$csv .= "\n";
		  
			$fp = fopen($csv_file_path, 'a');//ファイルを生成します
			flock($fp,LOCK_EX);
			fwrite($fp,$csv);
			fflush($fp);
			flock($fp,LOCK_UN);
			fclose($fp);
			@chmod($csv_file_path, 0666);
		}
		//----------------------------------------------------------------------
		//  CSVファイルの存在チェック(END)
		//----------------------------------------------------------------------
		
		//----------------------------------------------------------------------
		//  CSV形式での保存処理(BEGIN)
		//----------------------------------------------------------------------
		// 入力フォームで入力された内容の保存
		$csv  = "";//初期値
		
		
		//登録データが指定されている場合の処理
		if($countRegData > 0){
		
			foreach($regData as $regDataVal){
				//データ未入力の場合には空データで埋める
				$out = "";
				
				if(isset($_POST[$regDataVal]) && $_POST[$regDataVal] != ""){
					
					if(is_array($_POST[$regDataVal])){
						foreach($_POST[$regDataVal] as $item){ 
						
							//連結項目の処理
							if(is_array($item)){
								$out .= connect2val($item);
							}else{
								$out .= $item . ', ';
							}
						
						}
						$out = rtrim($out,", ");
						
					}else{
						$out = $_POST[$regDataVal];
					}
					
				}
				
				$writeData = $out;
				
				if (version_compare(PHP_VERSION, '5.1.0', '<=')) {//PHP5.1.0以下の場合のみ実行（7.4でget_magic_quotes_gpcが非推奨になったため）
					if(get_magic_quotes_gpc()) { $writeData = stripslashes($writeData); }
				}
				//先頭に0が含まれていたら「=」を追記　※エクセル先頭0消える問題対策
				if(strpos($writeData,'0') === 0 && $csv_data_esc ==1) {
					$csv .= '=';
				}
				$csv .= csv_string($writeData).",";
			}
		}
		//登録データが指定されていない場合にはPOSTデータすべてを保存
		else{
			
			foreach($_POST as $key=>$val) {
				$out = '';
				if(is_array($val)){
					foreach($val as $item){ 
						//連結項目の処理
						if(is_array($item)){
							$out .= connect2val($item);
						}else{
							$out .= $item . ', ';
						}
					}
					$out = rtrim($out,", ");
					
				}else{ 
					$out = $val;
				}
				
		
				if (version_compare(PHP_VERSION, '5.1.0', '<=')) {//PHP5.1.0以下の場合のみ実行（7.4でget_magic_quotes_gpcが非推奨になったため）
					if(get_magic_quotes_gpc()) { $out = stripslashes($out); }
				}
		
			  
				if($out != "confirm_submit" && $key != "httpReferer" && $key != "upfilePath" && $key != "upfileType" && $key != "upfileOriginName") {
					
					//先頭に0が含まれていたら「=」を追記　※エクセル先頭0消える問題対策
					if(strpos($out,'0') === 0 && $csv_data_esc ==1) {
					  $csv .= '=';
					}
					
					$csv .= csv_string($out).",";
					
				}
			}
				
		}
		
		//添付ファイル名表示
		if($attach2Csv == 1){
			$upfilename = '';
			if(isset($_POST['upfileOriginName'])){
				foreach($_POST['upfileOriginName'] as $val){
					$upfilename .= $val.'、';	
				}
				$upfilename = rtrim($upfilename,'、');
			}
			$csv .= csv_string($upfilename).",";
		}

		$csv .= (isset($_POST["httpReferer"])) ? csv_string(@$_POST["httpReferer"])."," : csv_string(@$_SERVER['HTTP_REFERER']).",";//問い合わせのページURL（不要な場合削除可）
		$csv .= csv_string(@date( "Y/m/d (D) H:i:s", time() )).",";//申し込み日付（不要な場合削除可）
		$csv .= csv_string(@$_SERVER["REMOTE_ADDR"]).",";//IPアドレス（不要な場合削除可）
		
		$csv = rtrim($csv,",");
		$csv .= "\n";//I改行コード挿入
		
		$fp = fopen($csv_file_path, 'a');
		
		flock($fp,LOCK_EX);
		fwrite($fp,$csv);
		fflush($fp);
		flock($fp,LOCK_UN);
		fclose($fp);
		
		//----------------------------------------------------------------------
		//  CSV形式での保存処理(END)
		//----------------------------------------------------------------------
}
//スパムチェック
function spamCheck($ng_ip,$ng_word_name,$ng_word,$stri_check){
	global $encode;
	$res = array();
	$res['empty_flag'] = 0;
	$res['errm'] = '';
	foreach($_POST as $key => $val) {
		
		//----------------------------------------------------------------------
		//  禁止IPチェック　引っかかった場合、メッセージを表示(BEGIN)
		//----------------------------------------------------------------------
		if(count($ng_ip)>0){
			foreach($ng_ip as $ng_ip_val){
				if($ng_ip_val == $_SERVER["REMOTE_ADDR"]){
					$res['errm'] .= "<p class=\"error_messe\">禁止IPアドレスです。</p>\n";
					$res['empty_flag'] = 1;
					break 2;
				}
			}
		}
		//----------------------------------------------------------------------
		//  禁止IPチェック(END)
		//----------------------------------------------------------------------
		
		//----------------------------------------------------------------------
		//  スパムチェック　※禁止ワードに引っかかった場合、メッセージを表示(BEGIN)
		//----------------------------------------------------------------------
		if($key == $ng_word_name && count($ng_word)>0){
			foreach($ng_word as $ng_word_val){
				
				if($stri_check == 1){
					$val = strtolower($val);
					$ng_word_val = strtolower($ng_word_val);
				}
				if(mb_strpos($val,$ng_word_val,0,$encode) !== false){
					$res['errm'] .= "<p class=\"error_messe\">禁止文字列が含まれています。</p>\n";
					$res['empty_flag'] = 1;
					break 2;
				}
			}
		}
		//----------------------------------------------------------------------
		//  スパムチェック(END)
		//----------------------------------------------------------------------
	}
	return $res;
}
//ダウンロードダイアログ
function csvDialog($csv_file_path,$userid,$password){
	
	if(!file_exists($csv_file_path)) exit('CSVファイルがまだありません。CSV保存機能がONの場合に初回送信時に自動生成されます。');
	
	if(session_name() == 'PHPMAILFORMSYSTEM'){
		$_SESSION = array();//既存セッションを破棄(トークン用のセッション)
		session_destroy();//既存セッションを破棄(トークン用のセッション)
	}
	
	session_name('PHPMAILFORMCSVSYSTEM');//セキュリティを上げるため念のためトークン用セッションとは異なるものとする
	session_start();
	
	
	if(isset($_GET['logout'])){
		$_SESSION = array();
		session_destroy();//セッションを破棄
	}
	$login_error = '';
	# セッション変数を初期化
	if (!isset($_SESSION['auth'])) {
	  $_SESSION['auth'] = FALSE;
	}
	if (!empty($_POST['userid']) && !empty($_POST['password'])){
		
		if ($_POST['userid'] === $userid &&
			$_POST['password'] === $password) {
		  $oldSid = session_id();
		  session_regenerate_id(TRUE);
		  if (version_compare(PHP_VERSION, '5.1.0', '<')) {
			$path = session_save_path() != '' ? session_save_path() : '/tmp';
			$oldSessionFile = $path . '/sess_' . $oldSid;
			if (file_exists($oldSessionFile)) {
			  unlink($oldSessionFile);
			}
		  }
		  $_SESSION['auth'] = TRUE;
		  
		}
	  if ($_SESSION['auth'] === FALSE) {
		$login_error = '<center><font color="red">ユーザーIDかパスワードに誤りがあります。</font></center>';
	  }
	}
	if ($_SESSION['auth'] !== TRUE) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<meta name="robots" content="noindex,nofollow" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>CSVダウンロード画面</title>
<style type="text/css">
#login_form{
	width:500px;	
	margin:25px auto;
    border: 1px solid #ccc;
    border-radius: 10px;
    box-shadow: 0 0px 7px #aaa;
    font-weight: normal;
    padding: 16px 16px 20px;
	color:#666;
	line-height:1.3;
	font-size:90%;
}
form .input {
    font-size: 20px;
    margin:2px 6px 10px 0;
    padding: 3px;
    width: 97%;
}
input[type="text"], input[type="password"], input[type="file"], input[type="button"], input[type="submit"], input[type="reset"] {
    background-color: #FFFFFF;
    border: 1px solid #999;
}
.button-primary {
    border: 1px solid #000;
    border-radius: 11px;
    cursor: pointer;
    font-size: 18px;
    padding: 3px 10px;
	width:450px;
	height:38px;
}
.Tac{text-align:center}
</style>
</head>
<body>
<?php if(isset($login_error)) echo $login_error;?>
 <div id="login_form">

 <p class="Tac">CSVをダウンロードするには認証する必要があります。<br />
   ID、パスワードを記述して下さい。<br />管理者以外のアクセスは固くお断りします。</p>
<form action="?mode=download" method="post">
<label for="userid">ユーザーID</label>
<input class="input" type="text" name="userid" id="userid" value="" style="ime-mode:disabled" />
<label for="password">パスワード</label>      
<input class="input" type="password" name="password" id="password" value="" size="30" />
<p class="Tac">
<input class="button-primary" type="submit" name="login_submit" value="　認証　" />
</p>
</form>
</div>
</body>
</html>
<?php
	exit();
}else{
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename=' . date('Y-m-d-H-i').'.csv');
	header('Content-Transfer-Encoding: binary');
	header('Content-Length: ' . filesize($csv_file_path));
	readfile($csv_file_path);
	
	$_SESSION = array();//セッションを破棄
	session_destroy();//セッションを破棄
	exit();
}
	
}


//----------------------------------------------------------------------
//  関数定義(END)
//----------------------------------------------------------------------
?>
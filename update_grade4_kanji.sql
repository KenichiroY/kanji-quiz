-- 4年生問題: 3年生漢字（および一部2年生漢字の修正漏れ）を反映
BEGIN TRANSACTION;

-- 6956: じゅぎょう → じゅ業 (業=3年)
UPDATE questions SET post_text = '語のじゅ業だ。' WHERE question_id = 6956;
-- 6957: 食べもの → 食べ物 (物=3年)
UPDATE questions SET post_text = 'ようのある食べ物をとった。' WHERE question_id = 6957;
-- 6961: たからもの → たから物 (物=3年)
UPDATE questions SET post_text = '円のたから物。' WHERE question_id = 6961;
-- 6965: ものれっしゃ → 物列車 (物=3年, 列=3年)
UPDATE questions SET post_text = '物列車がとおった。' WHERE question_id = 6965;
-- 6966: だい → 題 (題=3年)
UPDATE questions SET post_text = '題にとりくんだ。' WHERE question_id = 6966;
-- 6972: れんしゅう → 練習 (練=3年, 習=3年)
UPDATE questions SET post_text = '自で練習した。' WHERE question_id = 6972;
-- 6973: かん字 → 漢字 (漢=3年)
UPDATE questions SET pre_text = '漢字を' WHERE question_id = 6973;
-- 6978: ながれた → 流れた (流=3年)
UPDATE questions SET post_text = 'の中を水が流れた。' WHERE question_id = 6978;
-- 6979: もった → 持った (持=3年)
UPDATE questions SET post_text = '心を持った。' WHERE question_id = 6979;
-- 6985: せい → 世 (世=3年)
UPDATE questions SET pre_text = '百年を一世' WHERE question_id = 6985;
-- 6991: ひらいた → 開いた (開=3年)
UPDATE questions SET post_text = 'を開いた。' WHERE question_id = 6991;
-- 6992: たすけ → 助け (助=3年)
UPDATE questions SET pre_text = '助けを' WHERE question_id = 6992;
-- 6993: かなしくて → 悲しくて (悲=3年)
UPDATE questions SET pre_text = '悲しくて' WHERE question_id = 6993;
-- 6994: きゅう車 → 急車 (急=3年)
UPDATE questions SET post_text = '急車がきた。' WHERE question_id = 6994;
-- 6996: じかん → 時間 (時=2年, 間=2年)
UPDATE questions SET post_text = '食の時間だ。' WHERE question_id = 6996;
-- 6998: ぎょう → 業 (業=3年)
UPDATE questions SET post_text = '業がさかんだ。' WHERE question_id = 6998;
-- 6999: ともだち → 友だち (友=2年)
UPDATE questions SET post_text = '通の友だちがいる。' WHERE question_id = 6999;
-- 7004: さむい → 寒い (寒=3年)
UPDATE questions SET post_text = 'はとても寒い。' WHERE question_id = 7004;
-- 7005: しらべた → 調べた (調=3年)
UPDATE questions SET post_text = '読みを調べた。' WHERE question_id = 7005;
-- 7010: うつくしい → 美しい (美=3年)
UPDATE questions SET post_text = '色が美しい。' WHERE question_id = 7010;
-- 7013: おさら → お皿 (皿=3年)
UPDATE questions SET pre_text = 'お皿が' WHERE question_id = 7013;
-- 7014: けっ結か → 結論に修正 (元の文がおかしかった)
UPDATE questions SET pre_text = '', post_text = 'ろんを出した。' WHERE question_id = 7014;
-- 7018: そだった → 育った (育=3年)
UPDATE questions SET post_text = 'やかに育った。' WHERE question_id = 7018;
-- 7019: じっ → 実 (実=3年)
UPDATE questions SET pre_text = '実' WHERE question_id = 7019;
-- 7022: 食べもの → 食べ物 (物=3年)
UPDATE questions SET post_text = 'きな食べ物は何ですか。' WHERE question_id = 7022;
-- 7042: ぎょう → 業 (業=3年)
UPDATE questions SET post_text = '業がさかんだ。' WHERE question_id = 7042;
-- 7047: 食べもの → 食べ物 (物=3年)
UPDATE questions SET pre_text = '食べ物が' WHERE question_id = 7047;
-- 7049: はなし → 話, きいた → 聞いた (話=2年, 聞=2年)
UPDATE questions SET post_text = 'の話を聞いた。' WHERE question_id = 7049;
-- 7050: まなんだ → 学んだ (学=1年)
UPDATE questions SET post_text = 'を学んだ。' WHERE question_id = 7050;
-- 7055: びょうき → 病気 (病=3年)
UPDATE questions SET pre_text = '病気を' WHERE question_id = 7055;
-- 7056: しらべた → 調べた (調=3年)
UPDATE questions SET post_text = '書で調べた。' WHERE question_id = 7056;
-- 7057: れい → 礼 (礼=3年)
UPDATE questions SET post_text = '礼しました。' WHERE question_id = 7057;
-- 7058: もの → 物 (物=3年)
UPDATE questions SET pre_text = '大切な物を' WHERE question_id = 7058;
-- 7059: かん → 館 (館=3年)
UPDATE questions SET pre_text = '図書館で本を' WHERE question_id = 7059;
-- 7060: しゅ種しゅ は不自然 → 品種に修正 (品=3年)
UPDATE questions SET pre_text = '品' WHERE question_id = 7060;
-- 7065: ばん → 番 (番=2年)
UPDATE questions SET post_text = '番にならんだ。' WHERE question_id = 7065;
-- 7068: にわ → 庭 (庭=3年)
UPDATE questions SET pre_text = '庭の' WHERE question_id = 7068;
-- 7069: おもしろくて → 面白くて (面=3年, 白=1年)
UPDATE questions SET pre_text = '面白くて' WHERE question_id = 7069;
-- 7070: れんしゅう → 練習 (練=3年, 習=3年)
UPDATE questions SET post_text = 'の練習をした。' WHERE question_id = 7070;
-- 7072: どうぶつえん → 動物園 (動=3年, 物=3年, 園=2年)
UPDATE questions SET pre_text = '動物園で' WHERE question_id = 7072;
-- 7076: はなした → 話した (話=2年)
UPDATE questions SET post_text = 'が話した。' WHERE question_id = 7076;
-- 7079: しらべた → 調べた (調=3年)
UPDATE questions SET post_text = 'りたちを調べた。' WHERE question_id = 7079;
-- 7080: はん → 反 (反=3年)
UPDATE questions SET pre_text = '反' WHERE question_id = 7080;
-- 7084: よる → 夜 (夜=2年)
UPDATE questions SET post_text = 'かな夜だ。' WHERE question_id = 7084;
-- 7086: めん → 面 (面=3年)
UPDATE questions SET pre_text = '面' WHERE question_id = 7086;
-- 7089: まめ → 豆 (豆=3年)
UPDATE questions SET post_text = '分に豆をまいた。' WHERE question_id = 7089;
-- 7094: よ → 世 (世=3年)
UPDATE questions SET post_text = 'そうのない世の中。' WHERE question_id = 7094;
-- 7097: もの → 物 (物=3年)
UPDATE questions SET pre_text = 'すきな物を' WHERE question_id = 7097;
-- 7098: あそんだ → 遊んだ (遊=3年)
UPDATE questions SET post_text = 'の中で遊んだ。' WHERE question_id = 7098;
-- 7100: ばん → 番 (番=2年)
UPDATE questions SET pre_text = '一番を' WHERE question_id = 7100;
-- 7101: くらい → 暗い (暗=3年)
UPDATE questions SET post_text = 'の中は暗い。' WHERE question_id = 7101;
-- 7103: まもった → 守った (守=3年)
UPDATE questions SET post_text = 'を守った。' WHERE question_id = 7103;
-- 7108: ぎょうしき → 業式 (業=3年, 式=3年)
UPDATE questions SET post_text = '業式があった。' WHERE question_id = 7108;
-- 7109: うまれた → 生まれた (生=1年)
UPDATE questions SET post_text = 'が生まれた。' WHERE question_id = 7109;
-- 7112: しゅっぱつ → 出発 (出=1年, 発=3年)
UPDATE questions SET post_text = 'が出発した。' WHERE question_id = 7112;
-- 7114: もんだい → 問題 (問=3年, 題=3年)
UPDATE questions SET post_text = 'な問題だ。' WHERE question_id = 7114;
-- 7116: あそんだ → 遊んだ (遊=3年)
UPDATE questions SET post_text = 'よく遊んだ。' WHERE question_id = 7116;
-- 7119: かず → 数 (数=2年)
UPDATE questions SET post_text = 'はとても大きい数だ。' WHERE question_id = 7119;
-- 7120: まなんだ → 学んだ (学=1年)
UPDATE questions SET post_text = 'のはたらきを学んだ。' WHERE question_id = 7120;
-- 7121: こ → 子 (子=1年)
UPDATE questions SET post_text = '学年の子がいた。' WHERE question_id = 7121;
-- 7122: おん → 温 (温=3年)
UPDATE questions SET pre_text = '気温が' WHERE question_id = 7122;
-- 7126: むかった → 向かった (向=3年)
UPDATE questions SET post_text = 'に向かった。' WHERE question_id = 7126;
-- 7128: しらべた → 調べた (調=3年)
UPDATE questions SET post_text = 'で調べた。' WHERE question_id = 7128;
-- 7130: もち → 持ち (持=3年)
UPDATE questions SET pre_text = '気持ちを' WHERE question_id = 7130;
-- 7131: あつまった → 集まった (集=3年)
UPDATE questions SET post_text = 'が集まった。' WHERE question_id = 7131;
-- 7133: べんきょう → 勉強 (勉=3年, 強=2年)
UPDATE questions SET post_text = 'めて勉強した。' WHERE question_id = 7133;
-- 7143: おゆ → お湯 (湯=3年)
UPDATE questions SET pre_text = 'お湯が' WHERE question_id = 7143;
-- 7149: ぶつかん → 物館 (物=3年, 館=3年)
UPDATE questions SET post_text = '物館にいった。' WHERE question_id = 7149;
-- 7152: pre_text "ひ" は不要 (飛のひが重複するバグ修正)
UPDATE questions SET pre_text = '' WHERE question_id = 7152;
-- 7155: もの → 物 (物=3年)
UPDATE questions SET post_text = 'ような物をそろえた。' WHERE question_id = 7155;
-- 7157: とう → 投 (投=3年)
UPDATE questions SET pre_text = '投' WHERE question_id = 7157;
-- 7166: あつまった → 集まった (集=3年)
UPDATE questions SET post_text = 'たいが集まった。' WHERE question_id = 7166;
-- 7171: おと → 音 (音=1年)
UPDATE questions SET post_text = 'な音がした。' WHERE question_id = 7171;
-- 7173: どうぐ → 道具 (道=2年, 具=3年)
UPDATE questions SET post_text = 'りな道具だ。' WHERE question_id = 7173;
-- 7177: まもった → 守った (守=3年)
UPDATE questions SET post_text = 'りつを守った。' WHERE question_id = 7177;
-- 7178: えん → 遠 (遠=2年)
UPDATE questions SET post_text = '遠きょうで星を見た。' WHERE question_id = 7178;
-- 7189: まもった → 守った (守=3年)
UPDATE questions SET post_text = 'そくを守った。' WHERE question_id = 7189;
-- 7194: からだ → 体 (体=2年)
UPDATE questions SET post_text = '室で体をあらった。' WHERE question_id = 7194;
-- 7201: じゅう → 重 (重=3年)
UPDATE questions SET pre_text = '体重を' WHERE question_id = 7201;
-- 7205: ごう → 号 (号=3年)
UPDATE questions SET pre_text = '号' WHERE question_id = 7205;
-- 7206: こ → 庫 (庫=3年)
UPDATE questions SET post_text = 'ぞう庫にいれた。' WHERE question_id = 7206;
-- 7207: のんだ → 飲んだ (飲=3年)
UPDATE questions SET post_text = 'たい水を飲んだ。' WHERE question_id = 7207;
-- 7211: まなんだ → 学んだ (学=1年)
UPDATE questions SET post_text = 'しを学んだ。' WHERE question_id = 7211;
-- 7215: かんしゃ → 感しゃ (感=3年)
UPDATE questions SET post_text = '力に感しゃした。' WHERE question_id = 7215;

COMMIT;

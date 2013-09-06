<?php
/** Adminer Editor - Compact database editor
* @link http://www.adminer.org/
* @author Jakub Vrana, http://www.vrana.cz/
* @copyright 2009 Jakub Vrana
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
* @version 3.7.1
*/error_reporting(6135);$Jb=!ereg('^(unsafe_raw)?$',ini_get("filter.default"));if($Jb||ini_get("filter.default_flags")){foreach(array('_GET','_POST','_COOKIE','_SERVER')as$X){$Ge=filter_input_array(constant("INPUT$X"),FILTER_UNSAFE_RAW);if($Ge)$$X=$Ge;}}if(function_exists("mb_internal_encoding"))mb_internal_encoding("8bit");if(isset($_GET["file"])){if($_SERVER["HTTP_IF_MODIFIED_SINCE"]){header("HTTP/1.1 304 Not Modified");exit;}header("Expires: ".gmdate("D, d M Y H:i:s",time()+365*24*60*60)." GMT");header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");if($_GET["file"]=="favicon.ico"){header("Content-Type: image/x-icon");echo
lzw_decompress("\0\0\0` \0„\0\n @\0´C„è\"\0`EãQ¸àÿ‡?ÀtvM'”JdÁd\\Œb0\0Ä\"™ÀfÓˆ¤îs5›ÏçÑAXPaJ“0„¥‘8„#RŠT©‘z`ˆ#.©ÇcíXÃşÈ€?À-\0¡Im? .«M¶€\0È¯(Ì‰ıÀ/(%Œ\0");}elseif($_GET["file"]=="default.css"){header("Content-Type: text/css; charset=utf-8");echo
lzw_decompress("\n1Ì‡“ÙŒŞl7œ‡B1„4vb0˜Ífs‘¼ên2BÌÑ±Ù˜Şn:‡#(¼b.\rDc)ÈÈa7E„‘¤Âl¦Ã±”èi1Ìs˜´ç-4™‡fÓ	ÈÎi7†³é†„ŒFÃ©–¨a'3IĞÊd«Â!S±æ¾:4ç§+Mdåg¯‹¬Çƒ¡îöt™°c‘†£õãé b{H(Æ“Ñ”t1É)tÚ}F¦p0™•8è\\82›DL>‚9`'C¡¼Û—889¤È xQØş\0îe4™ÍQÊ˜lÁ­P±¿V‰Åbñ‘—½T4²\\W/™æéÕ\n€` 7\"hÄq¹è4ZM6£TÖ\r­r\\–¶C{hÛ7\rÓx67Î©ºJÊ‡2.3å9ˆKë¢H¢,Œ!m”Æ†o\$ã¹.[\r&î#\$²<ÁˆfÍ)Z£\0=Ïr¨9ÃÜjÎªJ è0«c,|Î=‘Ãâù½êš¡Rs_6£„İ·­û‚áÉí€Z6£2B¾p\\-‡1s2ÉÒ>ƒ X:\rÜº–È3»bšÃ¼Í-8SLõÀí¼ÉK.ü´-ÜÒ¥\rH@mlá:¢ëµ;®úş¦îJ£0LRĞ2´!è¿«åAêˆÆ2¤	mıÑí0eIÁ­-:U\rüã9ÔõMWL»0û¹GcJv2(ëëF9`Â<‡J„7+Ëš~ •}DJµ½HWÍSNÖÇïe×u]1Ì¥(OÔLĞª<lşÒR[u&ªƒHÚ3vò€›ÜUˆt6·Ã\$Á6àßàX\"˜<£»}:O‹ä<3xÅO¤8óğ> ÌììCÎÚï1ƒ¢ÕHRâ¹ÕS–d9ªà¹%µU1–Snæa|.÷Ô`ê 8£ ¶:#€ÊàCÎ2‹¸*[oá†4X~œ7j \\ÁÃê6/¨09\rŞ;Êô;Vù„n¨nªÊØŞ‰v«k«HB%À.k\">º¡[ë­\n¼¬°lÍápÔ9ÒcFZsÍÒ|Ú>6 œ5­l1VçÒÎê6ÃØà7¬Œ:£\"AzŠ½de´å˜ı\\í5*ÿÕ´Ÿ]£p[*‡Am)Kt[»\n8g=;úúæ2z¾àÃ|üòÌ£4˜t8.üÅìN#ßÊ²Œ¿B\"Ë9°Øú%¨ªè„HQwˆqd²àFûû¤\$&V¦–Q#Q'×ò‹_Øm¡Ì¡µ· ˆ¡Ş\rĞà´hà Xrt0j5¤Œñâ½W‡øõ4µúÇ×“€mÕÿ•‡\"CA¸F!Ïì—–h>ßb0ˆ0 7;84Kaˆ¨Ò	\0Ôp	a‡€ÑHXF±Š1:÷8ìU9H‰IÃ³Ë;ÙsQ7F¤‹cLpXM@e˜şÉüƒå+g(›Ğ73Oì3pÆî•b®lEE>·Chb%²DÀI8²ÉE'Ì	#)ù=%C£€jYù1°ĞyÕh;cA‘6ãjKû\ráÁİ9Â˜\$|­–’¼øËg-Zˆo—\0ˆ“òz‰³\$+D¿°æV±w*ÓWƒpæJ›†\\û²FŸO³'É²a1Àm,_Ú§\r‹ä1‡Päo±;\0Ğ5°æíÁe\r& 3ğ^\r™µ6åMR2T\0¹à5?~‚5˜—ªP>‚85h¹ nì1;ÒÍ\rRL8`Á\\¤Ğ@ŒÒ`;z\ní\0ĞÔƒ8Áˆ9RÔyZP@¾ib?Æ­v\$ƒ<Ä%	A\ré?œ\0ÇSÊ¥¤¡í  ÌBÃ4JÒ¨ƒ:Á`#Hi¿7Îµº+}àîªÕvî¥°o¦J´ÀVÚ°‰Ú9ÕĞßWÁ2¬Q®\rØTáD`¯fâÑ ‹­wéLµ˜³£œI]MKd7*rk*j\nASæÂjFÙ-[ezzÏr²íÊfUø3Øæ~\\àüZ£¤Z’”{)¢ò>>Ğƒp¿…*«¤Á‚;zDbáwÔÊ]¤mC\nƒõœè¨“ÃKBôŞB£¡Šm@Æœ¬ÏÖ´>§¶õÏÍwUÂ’İ*Nô(ba¡Æ¶Š@fvÙ)­µ`·\0ußD)mD@/4öÒãë9j‰ˆíú¹ªëHBm1ˆ²I¨£5DÀ¶RuEÆÒ9 åAÓ—=1bİ0çŠe¿yÔØ1ûãsä¡;´Äô‚ĞÚÃĞ-¥ØËó†]s¡ˆ5–\\…‘\n1;Èè­×QÜ^©Êb“¬i;YJ2ƒd!sÁ”÷ƒ#ñkgÃhŞ]êW)>VÆ…I—x]Ãr³Ÿ÷;6ÒJLcpr°d{pyó¹Mıè-UVHè5'\nt®„Ğ²¤lÓÊşpHÛÂÍo°eÁZ€Ï¨Óùq’eÉÑXÛFé`Gy\rç½!î›Ww*íÁ‡¿D¯ôu­t%Œ¹šdàQğë¯/Õp™:şihÀt&ú˜ğPÅÌe,JÍŒÊàtÃ!ìOØ7´Ò6µGgRúƒšC[òËskëvqU¡}y©hëAGV²Ş×Ï|ÚlF Ş…L^Ê.ñŞ]u&w!ßÚ[jnŒnÀàÚ[kƒCÅàvÀßÁ÷k–rmOÉ­¾ÙJ>°ïWTâ0Şÿ·¼\n£pMãCø®¹½b›tÂ÷VG|oy8ô§Èù¯cé°èĞú");}elseif($_GET["file"]=="functions.js"){header("Content-Type: text/javascript; charset=utf-8");echo
lzw_decompress("f:›ŒgCI¼Ü\n:Ìæsa”Pi2\nOgc	Èe6L†óÔÚe7Æs)Ğ‹\rÈHG’I’ÆÆ3a„æs'cãÑDÊi6œNŒ£ÑèœÑ2Hãñ8œuF¤R…#³””êr7‡#©”v}€@ `QŒŞo5šaÔIœÜ,2O'8”R-q:PÍÆS¸(ˆa¼Š*wƒ(¸ç%¿àp’<F)Ünx8äzA\"³Z-CÛe¸V'ˆ§ƒ¦ªs¢äqÕû;NF“1ä­²9ëğG¼Í¦'0™\r¦›ÙÈ¿±9n`ÃÑ€œX1©İG3Ìàtee9Š®:NeíŠıN±ĞOSòzøc‘Œzl`5âãÈß	³3âñyßü8.Š\rãÎ¹PÜú\rƒ@£®Ø\\1\rã ó\0‚@2j8Ø—=.º¦° -rÈÃ¡¨¬0ŠèQ¨êŠºhÄb¼Œì`À»^9‹qÚE!£ ’7)#Àºª*ÊÀQÆÈ‹\0ŠØÒ1«Èæ\"‘hÊ>ƒØú°ĞĞÆÚ-C \"’äX®‡S`\\¼¤FÖ¬h8àŠ²â Â3§£`X:Oñš,ª‡«Úú)£8ŠÒ<BğNĞƒ;>9Á8Òó‡c¼<‡#0Lª³˜Ê9”ç?§(øR‰#êe=ª©\n«Ãêª:*êÊ0ÖD³Ê9C±ˆ×@ĞÖ{ZO³ıêİ8­¦iªoV¨v¢k¨Arª8&£…ğø..ƒÑcH¡EĞ>H_h“ÎÕWUÙ5áô1r*œ¦Îö^Ğ(ÛbàxÜ¡Y1°ÚÔ&XHä6…Ø“.9‡x°Pé\r.`v4‡˜”¶†Ã8è4daXV‰6FÔÕEğHHºfc-^=äÂŞt™İx‹Y\rš%ö«xe çQû,X=1!ºsvéjèkQ2É“%ÚW?öÃÅ®Œ´æ=dY&Ù“¤VX4åÙ€Ì\\—5ĞßãXÃ¬!×}âæµNç¡gvÚƒWY*ÛQÅèi&ÈğlÃÎÑµZ#–İãñ Õ‘\rAç\$e°v5o#Ş›¢Øü¶5gc3MTC£L>vÎHéÜÃú–§<`ø°Ú* ]‚_ˆ£;%Ë;îÚV–ùi“Àèã4XÃé–'”Œ`ºªÉãi×j0g¶O±†Û¥“iæŒì©9·Æ™Û’dİFêÊÙk/lÅ¸–nÄÜc<b\n‰¨8×`‘H“ëeÅ}]\$Ò²úÖâ í°!†ÀÒÃC)±\$ °šAğ×`ó\0'•€&\0BÎ!íŒ)¥ò˜´5E)äÁàÒÂo\r„Ô8r`ûÈÌ!2ê­TÁ›s=¯DË©Õ>\n/ÅlğÓ‰’•[ı˜Å PÛàa‡8%ø!İ1v/¥¥SUcoJ¨:”4J+Bàó‡µv¯Jü‚\ráâÂb{ƒ ,|\0î°zöƒcÜªÅY§Ål®\nüœi.õÜ!äÛ)ü¦dmîJ«¯ÑÈ!'ÒÁë B\nC\\i\$J˜\"¾ëÖ2È+çIkJ––ñ\$Š‘’âG™y\$#Ü²i/¦CAb¾Ìb‚C(á˜:°ÊUX˜¯”2&	Ç, Q;~/¥õKy9×Ø?\r6¾°tVÊéÑ!º6‡CP³	hYëEÁÓÎØâ£ölñä(Ø–TáÒp'3ƒĞC<Ødc®¸?°yCçóşe0¼@&A?È=¤å%³A:JD&SQ˜Ñ6RÌ)A˜Ğb`0Ú@ˆéu9(!0R\n‡F „•ÂŠ ÄwC\\‰©Œ¤Ï…rÔäÜ™¡î¤#•~ğØ2'\$¡ :ĞØKÁ`h¬³@‰£Ebó¢[Ğ~¡Ñé’â TæÅlf5ª³BR]±{\"-¤Ğ\0è­ÊL>\rÇ\$@š\n(&\rÁˆ9‡\0vh*É‡°–*ÆXë!_djˆ˜ƒ†åpy¹‡‚¶‘`ájYwJ‚\$ØRªˆ(uaM+Áêníxs‚pU^€Ap`Í¤Iì’HÖ\n¨f—02É)!4aù9	À¢ê•EwCİĞ¡˜“Ë© ‰L×PÔİÄşAiĞ)êpø3äAuâÀöıAIAÉHu	ç!gÍ•’U”‰ZU·À¼c¤*­´À°M„ÃxfÆ:ËÆ^ÃXp+‘V°†±á²K‰C#+¾ ÖWhúCP!ÈÇÀ;”[pn\\%˜´k\0„ô²²,Ú¨8à7ã¬xQC\nY\röbÿ£XvC d\nA¼;‚‡lF,_wrğ4RPïù»HAµ!ô;™‰&^Í²…\"6;œå²êÎ=÷#CíI¡¸¯9fé'¬:¸ŸDY!ŒÿB+˜s¡xV†8lçÃ“¡\"Ïé‘ƒÍHU%\"Z6³Ôu\r©e0[Á•pÄßØa¡è.…À¶ +^`œ`b§5#CM‰\$² ûIçîËšAÌP§5C\rı S•dêWN6H[ïSR½µ·êß\\+Xë=k­õÎ»×ºş¼S”¶Ór^(¦ƒoo¶7™¬Ï©\\huk¢lHaC(màìşønRB†¤Uup³Ú2C1š[Æ|Ù½ùbeG0ĞÙ\"ìCG±²?\$x7Ğßn­¤\$ZÎ=ŸZÓ¦Ãsi5ËfÏí&ç,®fÓhiÆIÎyÖnî¶2ò0ÚœDvEüÃTïxôúMå{àô`Ü¤ÁGN#é‚Z,«Âƒ/âR\$”#\\I-	®„°—|Ä0à-0ı‰Nî¦P·ÉÒ¤;s-˜vô–ÏÒ†ÿ½‡nwGtï…n”¡ÒdiáH×|¥˜4¤(½¼+¼vò¥İ&ØÅ…’+KÀ£Ìñ™L\nJ\$Ô©ı†¨µ:\\Q<WB\"^—Íñ¤ºWTIB~Ñßq¬Éåğ}ó3ŸÎ¿\":şU‡á­Ö|\r5n(n™­ˆ‡ Ù7ƒÌOÁD}B}‹¼¨æÊ\0\r“voÜ•„…·Ø†_Jl‚Ä°•H3‘\"®[Ä¸âå¾ÔKŠAµ`ß–ù¯¦NÉÂü&(‚)\"ˆ fÿ&Å\0°¦ b¾ò¨lãF.Âjròî”şâJÂˆÆ\"P<\$F°*é|f/Ş! İOççŒpR Ç™„F#5gäbã Ä8eRDi¸É0“P‚+*¬üÆı™kZ;ÃpHh¦®l!è\0\r\nc›oÈ/¿úCBˆ<pyÀNTH½hêTç	ğ@éğpxÌ\$¢Šæ°ÌÀÖ48\n€Ò#îNU,Óˆš\$Pémò YKü¬\"H Ò †RıL¸ı‹®©DŸ\0‰¿âˆ€aWˆ`pûïşúĞgğ¯êlP¤Âÿoú:L€·Ê+\0 ]0±<)‚öN«xk\n(`cê„+r·k{m\"â3.0±H1’e*ZoeBÌ‹9\rÈøÚ\0RLi¥Q¨UğÔ‹`äÂ.”ûñÂ–o:Åd€´Â’µT7QœÑV »ÉDh‘âWæ´ëS1ñ	ñøgæ*2¯‘,†W)°Á@çÏ°T@C	Q(ñ,™Å4æ#d<Ò’\0¦! á\$˜ú2 {es¢´+…rÊ«şÍìÎJvY*ŒHPr\r¤‚†ÍTÜM\\\\`¼¿ívíàæ<ñ«&ÄnôD\\HHÈoj^@¢Ú	 Â<ñŠ†¯ëÆ8Š“*#fò©*Çş\r\nT§ \\\r²«*çTª^* ÚÉ Ê\$ª6oŞ7òĞRee8³ Êç²¡,Ò¥,Ó,`|9°K2Ï0r±+Ò§1RÖä\"È Õ* P*å¾È†M\\\rbà0\0ÂY\"ª\"ºUx†Ù`°±êÈ€àQ“E\rÀ~Q@5 ™5sZ³^fÀR@Q4ÈdÀ‚5Ãb\0@ÔFób/€8\"	8s‹8â<@šƒãìl2\$Sh± ¨\nÎR\"Uì43FNÉ«7\"D\rä4úOI3Â˜\n\0\n`¨``³â Y2Êğobñ3óË<n6“]<`ì\"’Ó Nˆ\"B2àZ\nˆüm¥ àEÀƒëîé\0ğ£üàZxÀ[2Â@,Â’’÷<Pİ?ô\rÔ8#d<@°´JUŠ¬K/E¡;\$«6óÌS”DU	l;¤,UÏLÎ’ñ7fcG\"EG€ó\$£¨\"E€Ù3FHÆ¤I“Ìãd‘=e	!ÒUHĞ‘23&jŠÈ¬Ó*úÂ%%Ó%2“,ŒÓJQ1HÌl0tY3öÁ\$X<CÄtà4ë_\$\0©ã>/F\nç¢?mF¬jÖ3¥p«Dá„HKœv ÈºÉœ\0Xâ*\rÊšåÑ\n0Ÿ‘e\nÎ%ïœºäÁ\riûÄêO€Ãfl‰Nö©M%]U¬Q¹Q½Lé­-†÷SÂ±T4Ğ! äU5T\nn˜di0#ˆEŠªM£ˆ³«i.ª°/U ¸é\rZFšúÓj„®¨;¢òíHÏâ˜d`m¤İ©ú–Ğ\nıt„ƒQS	eé²³|Ùi²šñ¬ÁQt¦ dò12,›öÁDYò1UQSU¬±cd±«µÄEˆ)\\«–¶ÂLö	ìF\$¶@öå³Vï{W6\"LlTÄëAò\$6abã‹OäêdrÌÉLp†c,’¨esÎ¨<2ì`Æ@b€XP\$3ààŒ@ËƒP,úKÍVÕ­^õ¾àÏM”‡Lö°¸ué1şÙ@îc•ˆt-ä( ¸ `\0‚9¶nïç2sb„¡Ê/ ĞFmä)¶ôƒ´ÿHl5ó@ÏnÌl\$‡q+ğ:®Â/ ¤ø§dŒÏ,òà\n€Şµˆì„£.4ú–’\$ ³w0\$€d·V0 È´\"¾ÃrìöW4678íVtqBau÷pÃ€ŠI<\$#Åx`Éwd9×^*kƒu×ofBEp	g2³Íóf4 à‰L!êr=¬\0§ñ\"	Ú\r<êÕhöÓÒæöˆU…%TÓhËëBkòº#>Å'C¥p\n ¤	(‚\r´ú2ö‡Â\"3â‹l•õMÔ‹7ıGÅx.ˆ,ÖUuØ%Dtø Ãw¶y^­Mf\" ‚ŠƒŞ(vU„3„u¬£J^HC_IU–YkS…—‡c_ylc†c]rF÷å×_q¤%†W#]@Ër²kv×3-ãcyÄÏVHJG<€Z¥öTè@V¸8œ\$6‡oƒ2H@˜\rã‚äÂª\0Âˆ=Øİö·æ¹\"3‹9zõ²:KõúÂu¯K >‚¢Œ¿B\$Ârİ.äJÒê<KõG~àP¿X´€QMÆ¹	XŒ‰w\$;Êæmp”Zp• åcK!OeOO¸?ïwpæÄæ‡¤í†Ö ¦ÚL—¶I\nŒğ•?9xB¤.]O:V®„˜ß9ßÃ.ÅmWŠ\0Ë—s>”*´l'«õk­Æoph»’èx¼‹‹«Şv´L`w1”÷° €è!¸M¨4\"òI\$Õ÷\"oõ\$À >Ë™Bea\"™ñŸDÿBoƒÊ¶ü+ì B0PxpŠ«&àá7Ã|p{|·Ï}7Ö°Â\$-P£‰‚éú@b„…¤õe¤ÆåÊVYmoMoŠ\0¢§£Nzn*>İÎ„€)¢ò·Èˆ×-H‡l!®“¼hpÆgÙË Š’¼Û&tZøãœ¤\0!‚¦8 É©¸¨àºZKŠê@DZG…Œ•Ÿº®øæ¶F€ç§©.† ˆ¼l¢üz%ÈÎ(ä¶xÙ}­ú'<šıÅª(°¼¥ú°ê<ÚXZÇ¬ºÚÑšà° É®g´ºí§ºò‡òw¯ºzÔz{°e¸'{;@å™±(&ø²ÅRà^Eèİ›xºå®›Y®ñ\"ËÌë¥MÜ’çç–VöÚ\n§5Ózl¥zrÔ[xŸ²Ëª’¥ú“»G\$O W @¤½À«Z¹xÇÎÕÄò­,Ì•”be»‰ 	ˆf£dÆ»Ğ2ûÕEÃ‹‹I¼D‘YTÙ%kš{ÎJ­\\\rºU N Å'¼_¾ÛÉ½»f|wŞµûàË,½l«7ªktø1RD>öĞ‹X‰ZîÍĞŠ­|y|Z{|×Õ¢Èî\r—é%;¬#\0eK¢	XÎ8&>7‡­ÖÎrhÎ:øLLª¶K*6U/\0004ØÎg™¢Eñc\n®jò•{Vœ[WF}#q İTÖû1abÆû\n‹1PÀÆ~íî((f+W‹?lîĞÑŠ·OÈüÒ#Dc€8€¸»Æ“šòkà\\@z×cÏ¦lñàé1lÆÌ—v~Õ ò(«L/cA%Ò#ÁbÈ„4Ó4Öíè÷ÍÑeZ2Ï®-\r…|ÑÎ1ÑŠ)ÎıK…<`¸Ù)2ÀW€“€X\røĞWÖd½>ÌÒ\nÌ­SX}Œ\rã‚¥EÔR¾(„â¼ÑÑ]`e9M•\0{Ù}šÕk+ƒÀË„¯ı³ÎÖ}Ò*&½'pƒ¤ÁË§ÎÏLó¬%DÂ}êwtëÕGwÔÀ÷Õ«oÕıçÎâ“CÀË×œùØSš«£´ıĞ\r<&ëB†8„mµ&‚€");}else{header("Content-Type: image/gif");switch($_GET["file"]){case"plus.gif":echo"GIF87a\0\0¡\0\0îîî\0\0\0™™™\0\0\0,\0\0\0\0\0\0\0!„©ËíMñÌ*)¾oú¯) q•¡eˆµî#ÄòLË\0;";break;case"cross.gif":echo"GIF87a\0\0¡\0\0îîî\0\0\0™™™\0\0\0,\0\0\0\0\0\0\0#„©Ëí#\naÖFo~yÃ._wa”á1ç±JîGÂL×6]\0\0;";break;case"up.gif":echo"GIF87a\0\0¡\0\0îîî\0\0\0™™™\0\0\0,\0\0\0\0\0\0\0 „©ËíMQN\nï}ôa8ŠyšaÅ¶®\0Çò\0;";break;case"down.gif":echo"GIF87a\0\0¡\0\0îîî\0\0\0™™™\0\0\0,\0\0\0\0\0\0\0 „©ËíMñÌ*)¾[Wş\\¢ÇL&ÙœÆ¶•\0Çò\0;";break;case"arrow.gif":echo"GIF89a\0\n\0€\0\0€€€ÿÿÿ!ù\0\0\0,\0\0\0\0\0\n\0\0‚i–±‹”ªÓ²Ş»\0\0;";break;}}exit;}function
connection(){global$j;return$j;}function
adminer(){global$c;return$c;}function
idf_unescape($u){$Ac=substr($u,-1);return
str_replace($Ac.$Ac,$Ac,substr($u,1,-1));}function
escape_string($X){return
substr(q($X),1,-1);}function
remove_slashes($xd,$Jb=false){if(get_magic_quotes_gpc()){while(list($y,$X)=each($xd)){foreach($X
as$uc=>$W){unset($xd[$y][$uc]);if(is_array($W)){$xd[$y][stripslashes($uc)]=$W;$xd[]=&$xd[$y][stripslashes($uc)];}else$xd[$y][stripslashes($uc)]=($Jb?$W:stripslashes($W));}}}}function
bracket_escape($u,$va=false){static$ve=array(':'=>':1',']'=>':2','['=>':3');return
strtr($u,($va?array_flip($ve):$ve));}function
h($P){return
htmlspecialchars(str_replace("\0","",$P),ENT_QUOTES);}function
nbsp($P){return(trim($P)!=""?h($P):"&nbsp;");}function
nl_br($P){return
str_replace("\n","<br>",$P);}function
checkbox($C,$Y,$Fa,$yc="",$bd="",$Ha=""){$J="<input type='checkbox' name='$C' value='".h($Y)."'".($Fa?" checked":"").($bd?' onclick="'.h($bd).'"':'').">";return($yc!=""||$Ha?"<label".($Ha?" class='$Ha'":"").">$J".h($yc)."</label>":$J);}function
optionlist($gd,$Qd=null,$Me=false){$J="";foreach($gd
as$uc=>$W){$hd=array($uc=>$W);if(is_array($W)){$J.='<optgroup label="'.h($uc).'">';$hd=$W;}foreach($hd
as$y=>$X)$J.='<option'.($Me||is_string($y)?' value="'.h($y).'"':'').(($Me||is_string($y)?(string)$y:$X)===$Qd?' selected':'').'>'.h($X);if(is_array($W))$J.='</optgroup>';}return$J;}function
html_select($C,$gd,$Y="",$ad=true){if($ad)return"<select name='".h($C)."'".(is_string($ad)?' onchange="'.h($ad).'"':"").">".optionlist($gd,$Y)."</select>";$J="";foreach($gd
as$y=>$X)$J.="<label><input type='radio' name='".h($C)."' value='".h($y)."'".($y==$Y?" checked":"").">".h($X)."</label>";return$J;}function
confirm($Sa=""){return" onclick=\"return confirm('".lang(0).($Sa?" (' + $Sa + ')":"")."');\"";}function
print_fieldset($hc,$Cc,$Re=false,$bd=""){echo"<fieldset><legend><a href='#fieldset-$hc' onclick=\"".h($bd)."return !toggle('fieldset-$hc');\">$Cc</a></legend><div id='fieldset-$hc'".($Re?"":" class='hidden'").">\n";}function
bold($Ba){return($Ba?" class='active'":"");}function
odd($J=' class="odd"'){static$t=0;if(!$J)$t=-1;return($t++%2?$J:'');}function
js_escape($P){return
addcslashes($P,"\r\n'\\/");}function
json_row($y,$X=null){static$Kb=true;if($Kb)echo"{";if($y!=""){echo($Kb?"":",")."\n\t\"".addcslashes($y,"\r\n\"\\").'": '.($X!==null?'"'.addcslashes($X,"\r\n\"\\").'"':'undefined');$Kb=false;}else{echo"\n}\n";$Kb=true;}}function
ini_bool($qc){$X=ini_get($qc);return(eregi('^(on|true|yes)$',$X)||(int)$X);}function
sid(){static$J;if($J===null)$J=(SID&&!($_COOKIE&&ini_bool("session.use_cookies")));return$J;}function
q($P){global$j;return$j->quote($P);}function
get_vals($H,$g=0){global$j;$J=array();$I=$j->query($H);if(is_object($I)){while($K=$I->fetch_row())$J[]=$K[$g];}return$J;}function
get_key_vals($H,$k=null){global$j;if(!is_object($k))$k=$j;$J=array();$I=$k->query($H);if(is_object($I)){while($K=$I->fetch_row())$J[$K[0]]=$K[1];}return$J;}function
get_rows($H,$k=null,$o="<p class='error'>"){global$j;$Qa=(is_object($k)?$k:$j);$J=array();$I=$Qa->query($H);if(is_object($I)){while($K=$I->fetch_assoc())$J[]=$K;}elseif(!$I&&!is_object($k)&&$o&&defined("PAGE_HEADER"))echo$o.error()."\n";return$J;}function
unique_array($K,$w){foreach($w
as$v){if(ereg("PRIMARY|UNIQUE",$v["type"])){$J=array();foreach($v["columns"]as$y){if(!isset($K[$y]))continue
2;$J[$y]=$K[$y];}return$J;}}}function
where($Z,$q=array()){global$x;$J=array();$Vb='(^[\w\(]+'.str_replace("_",".*",preg_quote(idf_escape("_"))).'\)+$)';foreach((array)$Z["where"]as$y=>$X){$y=bracket_escape($y,1);$g=(preg_match($Vb,$y)?$y:idf_escape($y));$J[]=$g.(($x=="sql"&&ereg('^[0-9]*\\.[0-9]*$',$X))||$x=="mssql"?" LIKE ".q(addcslashes($X,"%_\\")):" = ".unconvert_field($q[$y],q($X)));if($x=="sql"&&ereg("[^ -@]",$X))$J[]="$g = ".q($X)." COLLATE utf8_bin";}foreach((array)$Z["null"]as$y)$J[]=(preg_match($Vb,$y)?$y:idf_escape($y))." IS NULL";return
implode(" AND ",$J);}function
where_check($X,$q=array()){parse_str($X,$Ea);remove_slashes(array(&$Ea));return
where($Ea,$q);}function
where_link($t,$g,$Y,$dd="="){return"&where%5B$t%5D%5Bcol%5D=".urlencode($g)."&where%5B$t%5D%5Bop%5D=".urlencode(($Y!==null?$dd:"IS NULL"))."&where%5B$t%5D%5Bval%5D=".urlencode($Y);}function
convert_fields($h,$q,$M=array()){$J="";foreach($h
as$y=>$X){if($M&&!in_array(idf_escape($y),$M))continue;$pa=convert_field($q[$y]);if($pa)$J.=", $pa AS ".idf_escape($y);}return$J;}function
cookie($C,$Y){global$aa;$od=array($C,(ereg("\n",$Y)?"":$Y),time()+2592000,preg_replace('~\\?.*~','',$_SERVER["REQUEST_URI"]),"",$aa);if(version_compare(PHP_VERSION,'5.2.0')>=0)$od[]=true;return
call_user_func_array('setcookie',$od);}function
restart_session(){if(!ini_bool("session.use_cookies"))session_start();}function
stop_session(){if(!ini_bool("session.use_cookies"))session_write_close();}function&get_session_adminer($y){return$_SESSION[$y][DRIVER][SERVER][$_GET["username"]];}function
set_session($y,$X){$_SESSION[$y][DRIVER][SERVER][$_GET["username"]]=$X;}function
auth_url($fb,$N,$V,$n=null){global$gb;preg_match('~([^?]*)\\??(.*)~',remove_from_uri(implode("|",array_keys($gb))."|username|".($n!==null?"db|":"").session_name()),$A);return"$A[1]?".(sid()?SID."&":"").($fb!="server"||$N!=""?urlencode($fb)."=".urlencode($N)."&":"")."username=".urlencode($V).($n!=""?"&db=".urlencode($n):"").($A[2]?"&$A[2]":"");}function
is_ajax(){return($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest");}function
redirect($Fc,$Pc=null){if($Pc!==null){restart_session();$_SESSION["messages"][preg_replace('~^[^?]*~','',($Fc!==null?$Fc:$_SERVER["REQUEST_URI"]))][]=$Pc;}if($Fc!==null){if($Fc=="")$Fc=".";header("Location: $Fc");exit;}}function
query_redirect($H,$Fc,$Pc,$Cd=true,$_b=true,$Db=false){global$j,$o,$c;$ne="";if($_b){$Yd=microtime();$Db=!$j->query($H);$ne="; -- ".format_time($Yd,microtime());}$Wd="";if($H)$Wd=$c->messageQuery($H.$ne);if($Db){$o=error().$Wd;return
false;}if($Cd)redirect($Fc,$Pc.$Wd);return
true;}function
queries($H=null){global$j;static$_d=array();if($H===null)return
implode("\n",$_d);$Yd=microtime();$J=$j->query($H);$_d[]=(ereg(';$',$H)?"DELIMITER ;;\n$H;\nDELIMITER ":$H)."; -- ".format_time($Yd,microtime());return$J;}function
apply_queries($H,$S,$wb='table'){foreach($S
as$Q){if(!queries("$H ".$wb($Q)))return
false;}return
true;}function
queries_redirect($Fc,$Pc,$Cd){return
query_redirect(queries(),$Fc,$Pc,$Cd,false,!$Cd);}function
format_time($Yd,$rb){return
lang(1,max(0,array_sum(explode(" ",$rb))-array_sum(explode(" ",$Yd))));}function
remove_from_uri($nd=""){return
substr(preg_replace("~(?<=[?&])($nd".(SID?"":"|".session_name()).")=[^&]*&~",'',"$_SERVER[REQUEST_URI]&"),0,-1);}function
pagination($E,$Wa){return" ".($E==$Wa?$E+1:'<a href="'.h(remove_from_uri("page").($E?"&page=$E":"")).'">'.($E+1)."</a>");}function
get_file($y,$Xa=false){$Gb=$_FILES[$y];if(!$Gb)return
null;foreach($Gb
as$y=>$X)$Gb[$y]=(array)$X;$J='';foreach($Gb["error"]as$y=>$o){if($o)return$o;$C=$Gb["name"][$y];$te=$Gb["tmp_name"][$y];$Ra=file_get_contents($Xa&&ereg('\\.gz$',$C)?"compress.zlib://$te":$te);if($Xa){$Yd=substr($Ra,0,3);if(function_exists("iconv")&&ereg("^\xFE\xFF|^\xFF\xFE",$Yd,$Dd))$Ra=iconv("utf-16","utf-8",$Ra);elseif($Yd=="\xEF\xBB\xBF")$Ra=substr($Ra,3);}$J.=$Ra."\n\n";}return$J;}function
upload_error($o){$Mc=($o==UPLOAD_ERR_INI_SIZE?ini_get("upload_max_filesize"):0);return($o?lang(2).($Mc?" ".lang(3,$Mc):""):lang(4));}function
repeat_pattern($G,$Dc){return
str_repeat("$G{0,65535}",$Dc/65535)."$G{0,".($Dc%65535)."}";}function
is_utf8($X){return(preg_match('~~u',$X)&&!preg_match('~[\\0-\\x8\\xB\\xC\\xE-\\x1F]~',$X));}function
shorten_utf8($P,$Dc=80,$ee=""){if(!preg_match("(^(".repeat_pattern("[\t\r\n -\x{FFFF}]",$Dc).")($)?)u",$P,$A))preg_match("(^(".repeat_pattern("[\t\r\n -~]",$Dc).")($)?)",$P,$A);return
h($A[1]).$ee.(isset($A[2])?"":"<i>...</i>");}function
friendly_url($X){return
preg_replace('~[^a-z0-9_]~i','-',$X);}function
hidden_fields($xd,$kc=array()){while(list($y,$X)=each($xd)){if(is_array($X)){foreach($X
as$uc=>$W)$xd[$y."[$uc]"]=$W;}elseif(!in_array($y,$kc))echo'<input type="hidden" name="'.h($y).'" value="'.h($X).'">';}}function
hidden_fields_get(){echo(sid()?'<input type="hidden" name="'.session_name().'" value="'.h(session_id()).'">':''),(SERVER!==null?'<input type="hidden" name="'.DRIVER.'" value="'.h(SERVER).'">':""),'<input type="hidden" name="username" value="'.h($_GET["username"]).'">';}function
table_status1($Q,$Eb=false){$J=table_status($Q,$Eb);return($J?$J:array("Name"=>$Q));}function
column_foreign_keys($Q){global$c;$J=array();foreach($c->foreignKeys($Q)as$r){foreach($r["source"]as$X)$J[$X][]=$r;}return$J;}function
enum_input($U,$sa,$p,$Y,$qb=null){global$c;preg_match_all("~'((?:[^']|'')*)'~",$p["length"],$Jc);$J=($qb!==null?"<label><input type='$U'$sa value='$qb'".((is_array($Y)?in_array($qb,$Y):$Y===0)?" checked":"")."><i>".lang(5)."</i></label>":"");foreach($Jc[1]as$t=>$X){$X=stripcslashes(str_replace("''","'",$X));$Fa=(is_int($Y)?$Y==$t+1:(is_array($Y)?in_array($t+1,$Y):$Y===$X));$J.=" <label><input type='$U'$sa value='".($t+1)."'".($Fa?' checked':'').'>'.h($c->editVal($X,$p)).'</label>';}return$J;}function
input($p,$Y,$s){global$j,$Be,$c,$x;$C=h(bracket_escape($p["field"]));echo"<td class='function'>";$Hd=($x=="mssql"&&$p["auto_increment"]);if($Hd&&!$_POST["save"])$s=null;$Wb=(isset($_GET["select"])||$Hd?array("orig"=>lang(6)):array())+$c->editFunctions($p);$sa=" name='fields[$C]'";if($p["type"]=="enum")echo
nbsp($Wb[""])."<td>".$c->editInput($_GET["edit"],$p,$sa,$Y);else{$Kb=0;foreach($Wb
as$y=>$X){if($y===""||!$X)break;$Kb++;}$ad=($Kb?" onchange=\"var f = this.form['function[".h(js_escape(bracket_escape($p["field"])))."]']; if ($Kb > f.selectedIndex) f.selectedIndex = $Kb;\"":"");$sa.=$ad;echo(count($Wb)>1?html_select("function[$C]",$Wb,$s===null||in_array($s,$Wb)||isset($Wb[$s])?$s:"","functionChange(this);"):nbsp(reset($Wb))).'<td>';$sc=$c->editInput($_GET["edit"],$p,$sa,$Y);if($sc!="")echo$sc;elseif($p["type"]=="set"){preg_match_all("~'((?:[^']|'')*)'~",$p["length"],$Jc);foreach($Jc[1]as$t=>$X){$X=stripcslashes(str_replace("''","'",$X));$Fa=(is_int($Y)?($Y>>$t)&1:in_array($X,explode(",",$Y),true));echo" <label><input type='checkbox' name='fields[$C][$t]' value='".(1<<$t)."'".($Fa?' checked':'')."$ad>".h($c->editVal($X,$p)).'</label>';}}elseif(ereg('blob|bytea|raw|file',$p["type"])&&ini_bool("file_uploads"))echo"<input type='file' name='fields-$C'$ad>";elseif(($ke=ereg('text|lob',$p["type"]))||ereg("\n",$Y)){if($ke&&$x!="sqlite")$sa.=" cols='50' rows='12'";else{$L=min(12,substr_count($Y,"\n")+1);$sa.=" cols='30' rows='$L'".($L==1?" style='height: 1.2em;'":"");}echo"<textarea$sa>".h($Y).'</textarea>';}else{$Oc=(!ereg('int',$p["type"])&&preg_match('~^(\\d+)(,(\\d+))?$~',$p["length"],$A)?((ereg("binary",$p["type"])?2:1)*$A[1]+($A[3]?1:0)+($A[2]&&!$p["unsigned"]?1:0)):($Be[$p["type"]]?$Be[$p["type"]]+($p["unsigned"]?0:1):0));if($x=='sql'&&$j->server_info>=5.6&&ereg('time',$p["type"]))$Oc+=7;echo"<input".(ereg('int',$p["type"])?" type='number'":"")." value='".h($Y)."'".($Oc?" maxlength='$Oc'":"").(ereg('char|binary',$p["type"])&&$Oc>20?" size='40'":"")."$sa>";}}}function
process_input($p){global$c;$u=bracket_escape($p["field"]);$s=$_POST["function"][$u];$Y=$_POST["fields"][$u];if($p["type"]=="enum"){if($Y==-1)return
false;if($Y=="")return"NULL";return+$Y;}if($p["auto_increment"]&&$Y=="")return
null;if($s=="orig")return($p["on_update"]=="CURRENT_TIMESTAMP"?idf_escape($p["field"]):false);if($s=="NULL")return"NULL";if($p["type"]=="set")return
array_sum((array)$Y);if(ereg('blob|bytea|raw|file',$p["type"])&&ini_bool("file_uploads")){$Gb=get_file("fields-$u");if(!is_string($Gb))return
false;return
q($Gb);}return$c->processInput($p,$Y,$s);}function
search_tables(){global$c,$j;$_GET["where"][0]["op"]="LIKE %%";$_GET["where"][0]["val"]=$_POST["query"];$Rb=false;foreach(table_status('',true)as$Q=>$R){$C=$c->tableName($R);if(isset($R["Engine"])&&$C!=""&&(!$_POST["tables"]||in_array($Q,$_POST["tables"]))){$I=$j->query("SELECT".limit("1 FROM ".table($Q)," WHERE ".implode(" AND ",$c->selectSearchProcess(fields($Q),array())),1));if(!$I||$I->fetch_row()){if(!$Rb){echo"<ul>\n";$Rb=true;}echo"<li>".($I?"<a href='".h(ME."select=".urlencode($Q)."&where[0][op]=".urlencode($_GET["where"][0]["op"])."&where[0][val]=".urlencode($_GET["where"][0]["val"]))."'>$C</a>\n":"$C: <span class='error'>".error()."</span>\n");}}}echo($Rb?"</ul>":"<p class='message'>".lang(7))."\n";}function
dump_headers($ic,$Tc=false){global$c;$J=$c->dumpHeaders($ic,$Tc);$ld=$_POST["output"];if($ld!="text")header("Content-Disposition: attachment; filename=".$c->dumpFilename($ic).".$J".($ld!="file"&&!ereg('[^0-9a-z]',$ld)?".$ld":""));session_write_close();ob_flush();flush();return$J;}function
dump_csv($K){foreach($K
as$y=>$X){if(preg_match("~[\"\n,;\t]~",$X)||$X==="")$K[$y]='"'.str_replace('"','""',$X).'"';}echo
implode(($_POST["format"]=="csv"?",":($_POST["format"]=="tsv"?"\t":";")),$K)."\r\n";}function
apply_sql_function($s,$g){return($s?($s=="unixepoch"?"DATETIME($g, '$s')":($s=="count distinct"?"COUNT(DISTINCT ":strtoupper("$s("))."$g)"):$g);}function
password_file($Ta){$db=ini_get("upload_tmp_dir");if(!$db){if(function_exists('sys_get_temp_dir'))$db=sys_get_temp_dir();else{$Hb=@tempnam("","");if(!$Hb)return
false;$db=dirname($Hb);unlink($Hb);}}$Hb="$db/adminer.key";$J=@file_get_contents($Hb);if($J||!$Ta)return$J;$Tb=@fopen($Hb,"w");if($Tb){$J=md5(uniqid(mt_rand(),true));fwrite($Tb,$J);fclose($Tb);}return$J;}function
is_mail($nb){$qa='[-a-z0-9!#$%&\'*+/=?^_`{|}~]';$eb='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';$G="$qa+(\\.$qa+)*@($eb?\\.)+$eb";return
preg_match("(^$G(,\\s*$G)*\$)i",$nb);}function
is_url($P){$eb='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';return(preg_match("~^(https?)://($eb?\\.)+$eb(:\\d+)?(/.*)?(\\?.*)?(#.*)?\$~i",$P,$A)?strtolower($A[1]):"");}function
is_shortable($p){return
ereg('char|text|lob|geometry|point|linestring|polygon',$p["type"]);}function
slow_query($H){global$c,$ue;$n=$c->database();if(support("kill")&&is_object($k=connect())&&($n==""||$k->select_db($n))){$xc=$k->result("SELECT CONNECTION_ID()");echo'<script type="text/javascript">
var timeout = setTimeout(function () {
	ajax(\'',js_escape(ME),'script=kill\', function () {
	}, \'token=',$ue,'&kill=',$xc,'\');
}, ',1000*$c->queryTimeout(),');
</script>
';}else$k=null;ob_flush();flush();$J=@get_key_vals($H,$k);if($k){echo"<script type='text/javascript'>clearTimeout(timeout);</script>\n";ob_flush();flush();}return
array_keys($J);}function
lzw_decompress($_a){$cb=256;$Aa=8;$Ja=array();$Id=0;$Jd=0;for($t=0;$t<strlen($_a);$t++){$Id=($Id<<8)+ord($_a[$t]);$Jd+=8;if($Jd>=$Aa){$Jd-=$Aa;$Ja[]=$Id>>$Jd;$Id&=(1<<$Jd)-1;$cb++;if($cb>>$Aa)$Aa++;}}$bb=range("\0","\xFF");$J="";foreach($Ja
as$t=>$Ia){$mb=$bb[$Ia];if(!isset($mb))$mb=$Ve.$Ve[0];$J.=$mb;if($t)$bb[]=$Ve.$mb[0];$Ve=$mb;}return$J;}global$c,$j,$gb,$kb,$tb,$o,$Wb,$Zb,$aa,$rc,$x,$a,$_c,$Zc,$qd,$be,$ue,$xe,$Be,$Ie,$ba;if(!$_SERVER["REQUEST_URI"])$_SERVER["REQUEST_URI"]=$_SERVER["ORIG_PATH_INFO"];if(!strpos($_SERVER["REQUEST_URI"],'?')&&$_SERVER["QUERY_STRING"]!="")$_SERVER["REQUEST_URI"].="?$_SERVER[QUERY_STRING]";$aa=$_SERVER["HTTPS"]&&strcasecmp($_SERVER["HTTPS"],"off");@ini_set("session.use_trans_sid",false);if(!defined("SID")){session_name("adminer_sid");$od=array(0,preg_replace('~\\?.*~','',$_SERVER["REQUEST_URI"]),"",$aa);if(version_compare(PHP_VERSION,'5.2.0')>=0)$od[]=true;call_user_func_array('session_set_cookie_params',$od);session_start();}remove_slashes(array(&$_GET,&$_POST,&$_COOKIE),$Jb);if(function_exists("set_magic_quotes_runtime"))set_magic_quotes_runtime(false);@set_time_limit(0);@ini_set("zend.ze1_compatibility_mode",false);@ini_set("precision",20);$_c=array('en'=>'English','ar'=>'Ø§Ù„Ø¹Ø±Ø¨ÙŠØ©','bn'=>'à¦¬à¦¾à¦‚à¦²à¦¾','ca'=>'CatalÃ ','cs'=>'ÄŒeÅ¡tina','de'=>'Deutsch','es'=>'EspaÃ±ol','et'=>'Eesti','fa'=>'ÙØ§Ø±Ø³ÛŒ','fr'=>'FranÃ§ais','hu'=>'Magyar','id'=>'Bahasa Indonesia','it'=>'Italiano','ja'=>'æ—¥æœ¬èª','ko'=>'í•œêµ­ì–´','lt'=>'LietuviÅ³','nl'=>'Nederlands','pl'=>'Polski','pt'=>'PortuguÃªs','ro'=>'Limba RomÃ¢nÄƒ','ru'=>'Ğ ÑƒÑÑĞºĞ¸Ğ¹ ÑĞ·Ñ‹Ğº','sk'=>'SlovenÄina','sl'=>'Slovenski','sr'=>'Ğ¡Ñ€Ğ¿ÑĞºĞ¸','ta'=>'à®¤â€Œà®®à®¿à®´à¯','tr'=>'TÃ¼rkÃ§e','uk'=>'Ğ£ĞºÑ€Ğ°Ñ—Ğ½ÑÑŒĞºĞ°','zh'=>'ç®€ä½“ä¸­æ–‡','zh-tw'=>'ç¹é«”ä¸­æ–‡',);function
get_lang(){global$a;return$a;}function
lang($u,$D=null){if(is_string($u)){$sd=array_search($u,get_translations("en"));if($sd!==false)$u=$sd;}global$a,$xe;$we=($xe[$u]?$xe[$u]:$u);if(is_array($we)){$sd=($D==1?0:($a=='cs'||$a=='sk'?($D&&$D<5?1:2):($a=='fr'?(!$D?0:1):($a=='pl'?($D%10>1&&$D%10<5&&$D/10%10!=1?1:2):($a=='sl'?($D%100==1?0:($D%100==2?1:($D%100==3||$D%100==4?2:3))):($a=='lt'?($D%10==1&&$D%100!=11?0:($D%10>1&&$D/10%10!=1?1:2)):($a=='ru'||$a=='sr'||$a=='uk'?($D%10==1&&$D%100!=11?0:($D%10>1&&$D%10<5&&$D/10%10!=1?1:2)):1)))))));$we=$we[$sd];}$oa=func_get_args();array_shift($oa);$Qb=str_replace("%d","%s",$we);if($Qb!=$we)$oa[0]=number_format($D,0,".",lang(8));return
vsprintf($Qb,$oa);}function
switch_lang(){global$a,$_c;echo"<form action='' method='post'>\n<div id='lang'>",lang(9).": ".html_select("lang",$_c,$a,"this.form.submit();")," <input type='submit' value='".lang(10)."' class='hidden'>\n","<input type='hidden' name='token' value='$_SESSION[token]'>\n";echo"</div>\n</form>\n";}if(isset($_POST["lang"])&&$_SESSION["token"]==$_POST["token"]){cookie("adminer_lang",$_POST["lang"]);$_SESSION["lang"]=$_POST["lang"];$_SESSION["translations"]=array();redirect(remove_from_uri());}$a="en";if(isset($_c[$_COOKIE["adminer_lang"]])){cookie("adminer_lang",$_COOKIE["adminer_lang"]);$a=$_COOKIE["adminer_lang"];}elseif(isset($_c[$_SESSION["lang"]]))$a=$_SESSION["lang"];else{$ja=array();preg_match_all('~([-a-z]+)(;q=([0-9.]+))?~',str_replace("_","-",strtolower($_SERVER["HTTP_ACCEPT_LANGUAGE"])),$Jc,PREG_SET_ORDER);foreach($Jc
as$A)$ja[$A[1]]=(isset($A[3])?$A[3]:1);arsort($ja);foreach($ja
as$y=>$zd){if(isset($_c[$y])){$a=$y;break;}$y=preg_replace('~-.*~','',$y);if(!isset($ja[$y])&&isset($_c[$y])){$a=$y;break;}}}$xe=&$_SESSION["translations"];if($_SESSION["translations_version"]!=1712857100){$xe=array();$_SESSION["translations_version"]=1712857100;}function
get_translations($zc){switch($zc){case"en":$i="A9D“yÔ@s:ÀGà¡(¸ffƒ‚Š¦ã	ˆÙ:ÄS°Şa2\"1¦..L'ƒI´êm‘#Çs,†KƒšOP#IÌ@%9¥i4Èo2ÏÆó €Ë,9%SiÀèyÎF“9¦(l£GH¬\\ç(‰†qœêa3™bG;‘B.aºFï&ótß: Tó¡”Üs4ß'Ô\nP:YîfS‚®p¤Øeæ,¡ÌD0ádFé	Ò[r)+vÜñ\n¼a9V	ÆS¡Ş´kÌ¦ónÓcjäADS!¦2rÉœ¢›ĞQBğsÛŒ–£}tİÍÊm˜jl{­a2Äj¤2Bb<áA\"_?§2yÌÒÁBAˆ´H3û\rAE3.ÀÆ4K`ò;°Hö­\0ÚãBÆÓ/ØÊ‹ÁîZí\$ÀPŒ97Øê1\rC,*\$°ì¤ ‹#şŞÎøÒ<Êt.0¤Á\0Ğ¼ãƒ»sÖŒ±šLŸFã²\rÑäZ”)c¸\$	Ğš&‡B˜¦cÌ´<‹°¸ÚŒ˜˜ê °Kù\n)£#B0CcU	R3‘ÃØ4¤\"¦)Ì°Ş5°ÁpA´¬H­&„h7%Ï2 ã¨Æ1©#˜Ì:‰Cø9±å\"8@@—*1ËŠ\"É­5J±2J8\r(Èëú¼»ÊòD3ÑiDé;O\0ÇY;ƒLìÉ%J‹)Z¹îŒ€<V•\$cB3¡ÑĞ^÷\\Akèä+C8^Ã]cÃ\nÃÕ¡xDË6ˆél‹è²Ø5„Ağ’ª¢ñ`èã|Ÿ§Íë7´¨Óµ@<jk09;Kì–°£L#\rNºëú/ìµ„TBj\n@ Çİøâ|(JD€¤SªL²Œ©BT–%É€ÜÍÙyì™äé,&2'Ê}(šÕYBî¼«Ön1€P¨7£C¹”	â˜¨MK%)&´’1Cl)´ñÚ’ñ³ê‰ğÆ¾\$«päĞÕ»c4Ò2P, ¶Ø£¨úB‘ëC=(‰‰#;XÁ\0Œ*ekbB–Ô#’šã\$â`ºN,¥|Â¦º}\$l›iUBOIGB9°õr¨ v8Ë«İ\n2%\$±àîÉp4Š)uª·È\"ëŠ|Œğ¦DŞ•M40ÈÏß¸rÖøÓgì‹3{í/óAûw3ıS¼/cİÉ#Ì3o•T~ÄJ¢D}.¡ı?Äëª\r€¬1§pÆÙù	Ô6R0Ã˜`iÁåˆ˜Òp©\niœm€€*…@ŒAÁNm°d¶’ƒØñCyâtÊ™ŞVLğûÆ€I\$—1\rÉAg	ù&†DFÀ\n&¨02•(i\nğgM67ƒdÊsç	Ü7A¨`Ìzäú\nAbPs‹Šm/%1’¢u	£+E5£a‰@¼œ›€PC#Åôá­ëa³šBGø46×Ë\"©3ÁR\$…(òŒCŒ6&=W~xKGĞ¾L™@ÒRdàc“ÌP‚à";break;case"ar":$i="ÙC¶P‚Â²†l*„\r”,&\nÙA¶í„ø(J.™„0T2]6QM…ŒO!bù#eØ\\É¥¤\$¸\\\nl+[\nÈdÊk4—O¡è&ÂÕ²‰…ÀQ)Ì…7lIçò„‚E\$…Ê‘¶Ím_7—Td…Ôâ¥¢ÊQÔ%Fª®ÎâPEdJ£]MÅ–iEµtØTß'í…œ9sBGeHh\\½m(AÁ¸L6#%9‰QèJXd:&§»hCªaÎ¡RÄPcÕ¹åzÀ†¸Ìnø<*©°®Ì¡g\n9††%‚‡h5ut.—³¼QS…œ\nÅÍÄ¶p{š¯l-\nˆ†;„Dğ¸Ê\nã ën¹ÙÒÆI‚Îífˆêg€ŸgxuµŒãlÂ§„R¡\"erğè¿êä®Ğ1·ÏJpÃ¡ª°¹ê¹^‹ˆ!P»¤nXò2£ƒpŞ¨^qXbœCéÑV)dE+.êNC?Ëà‘•	Âu\r³\"ì2mÄ~±?­›¶–¤e{6È!ÏÚ‘1H;à­z¶\\§âí4²š¥í²–Æª!R¨2os\n©?Éé±8²Š’™¬s›7¸´\$	Ğš&‡B˜¦\rCP^6¡x¶0Òƒ»À	 ³·Úw	¬e²„î\$qÚÄåC Øà7\r2İb˜¤# Ş5Œ£r	V›¹°SÑË°±é«\"´+œï>¶lÛ(¸!®\0°!­’Îè.èm\0ù¦°¸%ì<…¡Å²¡'‹læ„Î¢àC¡7Cœñ›e„ÆË¬‚ŞY2’LÜrt¬	yi¥ÑµÿSnv‰¸š0c Ê9†(9ãxåŒ£Àà4C(É`‡ƒ@4d£0z\r è8Ax^;çpÂ2\r£Hİ‹…ØğÎ×: ñ‹\rÃ˜Ò7ÁxDƒä3Œ£¦^/ŒC`Â7\ra|\$£€Ø2µÈèã|Ò„(è4\rğèè7í£\rp\r#¦3‹ºŞÍv¡­³?e\$)JÕ«ŠôÂÎğK…@àü¡¨›ÛJ·\rµ‹Š'@%\n%üÛ.©:¶Y^2uûe,è2ˆ£)êròàˆ_®)s‡pŞêm©n/â(	â˜¨…¢5\"ÆT®\rƒÚ°òÚ[ª*'‹×às=ö®¡o7µ–J½\\üv2¦±¸T\rR¬sUš(	‚n,ã¨@1ëa\0êØƒ{;¨1Î˜Q	€€3–Æ°ieA*èil,y½7˜ƒxm#l\\;4êö‰Ğ°(.¨ê!abTo7¨e€–t(QI\$0NÆL‰ã@+\n7…ĞÂ©£s…9o.%Õ).ãªNß»ùea†‚LÆCi‚-ª6 İød,Y¨€Óš‹Y¥@V–p˜]™/ã\"Œƒ9¶_±œÈå¹ÔLƒ`P”#ljYñ'*€‰¯èlu\r.r‘¶¼Â\0ø+'A: Ø\nÃl\r!Œ5‚™\0à€a\rÔ2ÅöÚÜCHf-à:p¥hm¬T)ZÏ\0U\nƒ€@®C°\rÁœ¨:¹sá…WÄ•í›Râ{œùI“hÂÈUæ¡¨%D¨ûMä’ôß„á9Ñ1£Øö‚Ó©4•n¡8yU<p>Fš(|“Å¸ FÌ ¶s£ÕaÂsòyŸRÓ'—“£°ëM¼à;Á2\0†F|ÀU€AµJØÄÔPâû2“L„–]d±\$äĞïC\n+‘ê†‡½eÍgn«‘˜oµ\$è˜]Ø„}35%'M{O(L4a«\0";break;case"bn":$i="àS)\nt]\0_ˆ 	XD)L¨„@Ğ4l5€ÁBQpÌÌ 9‚ \n¸ú\0‡€,¡ÈhªSEÀ0èb™a%‡. ÑH¶\0¬‡.bÓÅ2n‡‡DÒe*’D¦M¨ŠÉ,OJÃ°„v§˜©”Ñ…\$:IK“Êg5U4¡Lœ	Nd!u>Ï&¶ËÔöå„Òa\\­@'Jx¬ÉS¤Ñí4ÚzZØ²„SåØHİMS àè]şOâ”ÕE2şÕ\\¶J1‚Ê|úĞ¦[ÉiõL¢™_?€Pµë\n~bÂ¨‡#óªm\r/ƒÚÔt7½Bš'Ÿ¹C¶˜]¾sl¾ğæö2G©ÓÔ¶ĞæŠÌï^TÈ˜s±¢ìñ<\neU>¢‚€c¶½Uõ>İ£³ëÄÖS ïL^>Ê#–Â²Í4\nÙ¾jRñ©êêÜâ’hªòÀ\r©*§½ÏÚÙÂOù~ÿ1êÃdÁ+ì*\\×¬lº·=‹{Şæ&ÌK^W2Kb£”î,.¢*{ SëJ¶¥*«\" d%É¯QQ³h\nÏ·\r*\0ØÊ#Â H\"62:O™>„³dÂ¼Cq„¿1r#*B7\ràPHÁ iB†,k=/“Š{n½¯ÎäÀ¨(’“_\rA­{İK¦\0©mê‰ÃÏ\$^‡£Û\nÒ/¼q]`°áj¾·-Å’Ÿ)GŒÁ\nLM‚OÓ3Í°ÓrÜícKÎDĞÈ¥«a@¶sZ· ñårš¯ÒY~¡ÑšñiÓ‹ƒRÁo3¦Î“Õl×U»&Y·\nrZ-}Ğ¼+¤]×<±ãÎÕ3…3„S1Òx\$Bhš\nb˜:Ãh\\-XèÔ.ÉVò\0–=h‰.”Õã©BUTæ¡Òi«¶EC`è9S2;j×Bêz¶ëäB¬b˜¤#4õ|Ô)«3*¢É^_ß·=¨îá2ÆF€G\$ülÆMCjà:ò¦\rwá,Üçì”¤î©J,ìÌBÄ>¿<Eö¥\"q3/¬JZÙ\r2¨uévDÁL‡o«qoÃ÷röoug\n—ßÖğíËèƒÒŸ\\	V«;H’6Ú”<\0š0c Ê9‡X9ãxå6£Àà4C(ÈàÂ\rèÌ„C@è:Ğ^şˆ\\0ŒƒhÒ7uáwl3…ã(İîpÜ9#¾ Â9ã(éäãØ0ÃXD	#hà6£o¼à/ ø¸‡0@ÿƒ h\ré°:øCXe¤:;^_£ÿ.§ ß 6èYn)G=€#äè¨”‘À\n (5÷NaT²)ƒÅå(F`Ar_‡Ñ«¦Vô»	2E„Æ-„³òº—ÖÉaF°Èï¬ êñ*,¹‚®°JÊË?%ş\rse‡-òÜÁÚÄ^ˆìˆÚ™ctdÓÌT4…dè›ƒ2@µŠä\$í/—<ÂOÒ_(	…Ô °@xS\n„:B—‡LÓI‘lHÈ<Áõ„°LšhŒk¨€3õ,« +x)E	%–tà”™Je¥<¶´ÛËDrmšŞA\0c~€€:¿°ŞõÃA¤3‚\0¦B` Á¥ş\0ì^F\nµú&ÀÒş´‚“(9ğÚHxv{+	&CŠi:] |°vœ\\?OK\rÉ0‰È˜ç¢f(‰¤@·´fêQã^§²ÂI¤œ³ætOµòTVÊy2QR©%“è\$ólÑ´)–ÀèåŠ¨T0q¹6äiby1’Ú\\€Ã8€v!È4Ì÷øû`4\r—À2\\úC@i§!…ùX6Û£ŠÎŸ˜Q¬VßÍ©±©’ÑšD¨ü<F‡6y*âğ¸œDdˆjÅ£Å%ª“mk–§šH¨šRµfXç´®õ²—çqß\nu8´šP˜hZe¡¦Ö~VÈÚOKe\n3k®Ğr‚7\r€¬1†ÀÒÃX |ÓgØ`•:\r°,4†`óØÀ\nÓ2	ĞêëA\0b‚O\\P¨h8x1Ú@ÜË­}dÄ5;Rr½è‚x70íl-%(o62ƒVĞ¼kô|¤Õ>ãÕ‹”®b 2e†èQ‰äP¬mÖ=&êØ¥ÂË!{SUÍµ™N¢‘'jT—HxÚ®hñW!Cum÷£qGŠ\"3ŠˆŒ€0»îÛá*ıTˆ·¶\"øC–L\\…×ô¶#T,•ÈÀ™(‘e DÂn¼´p8Í,#j¿ÔÌr\"Fˆ[€ ™/Ã#×øÛ@wm©ëêZ×î]Œˆx®ÕÉôKaÌ—|jœM9K\0—£~SØ\\–*7(2uçs¯\")÷´ŒDü~‹\nŒ–™ŒÑ^åu‘ĞÃs<9)°ÕºE<¯©`";break;case"ca":$i="E9j˜€æe3NCğP”\\33AD“iÀŞs9šLFÃ(€Âd5MÇC	È@e6Æ“¡àÊr‰†´Òdš`gƒI¶hp—›L§9¡’Q*–K¤Ì5LŒ œÈS,¦W-—ˆ\rÆù<òe4&&#¬°o9Læ“q„Ø\n'W\r‘¢hc0œC©°Ã1DÌ†“|øU:M’ÃÑ„Sº`§ñÔX :âqgLnbÚ §Ç ¦SÁĞÊnŒ›õR­I¬š¦šCM~Ã1*N-tØ'Éd¦›†Är¡‚ˆ† ‚èh´cˆqı?\$…lá‚‹SÆ8eÂ™N–œq3_9‚ˆ¸3¥£ŠmĞp|+H(‡ªñ˜`æãÎ8’7\r#Òš/È2†Œ˜e›0H@ IğÜÓ(ê“£ŠF†Œ\0Ä<´ÀHKÃí3Ò<¹*Ø¡xHÅÁ‹ì—¯\0Rh8¸Cb;\réHØ6\rã'J2Ú™ejm\nˆ#ÁhäœÂHï¸ääBé@Ê1¼fe\0HB Ê(ÀêŠ´È›P—¬ÄÈ\$PTôˆ£pí±á:ì“ÎĞè¨C£Ñ,CÜ»/À!p)ÏCİ>Nîe\0PR¸ÊŞKRàå/L\rll91Â@	¢ht)Š`PÈ2ãhÚ‹c\rd0‹®R‡A\rĞœ¸Ç\ntÌäÏ§\n3Ò6<pXå;Œp¨7m8@!ŠbŒçNÈ2Á–93íœú’Ã	RĞ6¯®c1uÄ&AÔ«‘]¦.šS#ª„®‚tT ­ûx§ èÄ©O&p6Om à4Ô£.öÛ‰¢h¶@0äÂlòşû94· «`ík&ôğšı4É0àıcºÑáX`Ëlâh490z\r è8Ax^;èr>›Bi‚Ğ3…í>šÒ´íHÜ„KÚJ3¼YØ¾ÑÃXD\"Œ Ë\rà^0‡Íbjñ:ëÚšŒ6k?ßTt)x¯*ëLÔA°Râº¹ë¾òĞ¨{Ö£„€( Ãz-†ºˆ(P¨…-j‹@ØÊ˜Ñ“ŞY¥—\n¶6¸èêtP'‚¼\"­h@ÿŒnFÈš*)ˆ™ÂØ<( 'Šb¢\rÑéò0šõQlŠY˜ë6„\rAÈ„\nâÙØìp¤¢æRàÒ;(¨íp•¡5Yµ‰ˆ²7ÂëÅ:²ƒz>CËP@)Š\"bˆ@#\nœe‡IëOî„3#\n–9/sŠ4ƒ>Ä\"èMúZl-Ï%Ärc”C-İ<§²ØAÃ‘\$Oğiê’7jzaoåÌº¸\"ôrY9™ôBĞìGJÚ-)Ü5‚\nÛßa84Åì¹ÃğÂæ—9C1Ë¡‰ bó™,QÈV&å©\") áœa‹Ä(càq=M°tœ†óJËÂDj	¨µğc0i\nJ5Á\$(§‚3…\$K<ñ†ÀVÌXc~íî\$TVClql] Œb1’Oáˆ–À@B F á>(#ƒ<bdT´B%'\r©ãsq¸»(ø)c¢•È¡“ÂEÈËš@ÉI_®\n_ğ\nŠ\$‘U+sN‘«É/Êüô˜ƒh'4èü0Œ1N|b‹ãAò\"E ÀW¡%¸´ _’98RÊ€™>é>çkï‘'!çÌH×,åR\"–D\"TœƒÒ{C¡£G@‚ØDzB@Há<@#ı)!	ÒF…è,ç0HT+”HóE\n\"-:ì7‡@";break;case"cs":$i="O8Œ'c!Ô~\n‹†faÌN2œ\ræC2i6á¦Q¸Âh90Ô'Hi¼êb7œ…À¢i„ği6È†æ´A;Í†Y¢„@v2›\r&³yÎHs“JGQª8%9¥e:L¦:e2ËèÇZt®\"=&ŠQÁŠ¯œØ¦ ¦*öEjTˆ†ÔØk<ÊÄ\0¢Q„ôy5‚ŠÇ“è\n(¨³SlŞLÅ_MGHå:ÅL=(†ã¾€kT*uS‚²i­×AE\\¤ìaÊf¶Äèy8ALDdÔæl0‚ˆ›®4Â b#L0æ*`Êtb&ÏF3((„iœ¦ŠĞQNjÅR‚ˆæSy·r4õJfSÔxÛº\"™\r'IN¯[¯Ø‚‰]ù¿‹\r#@Ø‘oš6\nƒ“_®+2/\$F)B-bs<‘1ÈË 5¸!(ÈC&ÃÑDÛÄüBÉ¬àPÂ7?ÃsªÁpHFÁ‹<ÀC*(Ãc Ê¢(C³º2¢ÃäÃ‰‰ƒæ#[©›`¡hÚ0£\0PŸ!i«€üŠ´|6º,8­\r2,<ÊLÌCxĞİKïäÆë#`@‰ÎÉÜĞÃã,µ.\rp!O#Ì÷0@eİED´¶Œ/4<òOüüR40³¥l¡ˆG¿B@t&‰¡Ğ¦)C \\6…ÂØåYBì¼2B57²I¸#`è9HğPËb\"„Æš«Ş5§\0†)ŠB0\\N°’vÊ2R6®Ğ‰2=¬ğê•ËõÌ%\n&ÈŞ1:04;1È²Ş5Üò8çóıİ4crÆ\\\nl1eêHœ'P¡R©¿—äş;7•Öà‰¼\"©89£—vÆÜ£µódÙm®5¸“<0Œi¨@a—H£T(Yj*IÎØĞ\r³Š 8¸Ãk‡:@2ŒÁèD4ƒ àáxï«…ÒPÚìXárR3…ãæ«, İ‰áä0C;Ÿ§ã€7\ra|\$¶PÊó\rÃ¢¸ÊxÂ`z&¦*ÊéEYM‚0(ÉÊ•­«~ÅP“¾Lİˆ}Ø™Ğê8`l‡-•3(È‰1M}˜1\0 \$\n1Í³3¶\n€R•ÑÔ!±NM£-¥é‰B™æXbwf1Ş6&¶¸µŠBVæDÆ4#šW7ÌCL\$Ÿ¨-¢|(	â˜©Ğ68@-o—]¹`*éEÉìcÁ~±\rûó4¿C0iáÔ—v|ƒ’C†¹H=ãùXH \naD&RNUƒig2<â@H˜m\rìÄ›`¨íN90u‡Ğü?¢4˜úÇ(-ï“ Ş†Î8bO'9¥°ôŸš9ˆ†ĞáC°@C[~%=!uB„CÈp¨DS€OÓa<ˆ!T€4åE	pÚ0—‚VDRÜ\rå|P³Ó¢îÑñóeä‘¿FÖ]Kºù\n©±'Ç¸n›b9\rpFÇ2~i’[0\r&®>F‡#_`lwv%w‚Ã²E\rÑ½@.BÀ{”3ÁQFzMFõ ‡áø¡=¡„÷Ê/&%+S’¢OÊÅ@‚Ã\r€¬5” ×&ÄÚÜ#Èdšœ’ˆÆbJÇeNÀê-ÀôÀqó4p\\Ò°VdÂT‡6‚ÍĞ‡düÌ‚¨TÀ´<ò!Q)…‰úNÊ™V¬°lQS¤9Nµ)äôª=ÊUà‘©é=‘„í–°dWÆr\"DÈ©#\$lŸ«ÓaË²øNŒdÈ\nÛ¤:&=4æ‚Y™ñ¼è—#kŸÚ06æ8:šÓ&Gš34R/B¦°Óò,¥–£¸ˆù!¨aúƒ\$‹¸}…Ì„!øÈƒZ)ŒiÂ2‡š¥SÃZ-:ˆ\rfÍázoÑòC¤e„°!X\no¤Á£ÔRÚ.ä‰Ø ‚éDN	Í^è¶(Ò6¸p";break;case"de":$i="S4›Œ‚”@s4˜ÍS€~\n‹†fh8(o…&C)¸@v7Çˆ†¡”Ò 3MÃ9”ç0ËMÂàQ4Âx4›L&Á24u1ID9)¤Îra­g81¤æt	Nd)¥M=œSÍ0Êºh:M\r†X`(r£@g`¢\\˜İ*LFSef\nŠg‘†e£§S¡èên3àM'Jº: CjØ³ÉÃR\\ÍØCÔv«\$«™k'JÙÊ¡/4Hf˜,Ş- :ZS+Œ2½Åêmò\"Ô˜é¹“_ÍÆ³.3pB€°Ô‡ Q;šz;Ã\r`¢9”ŞmæÚ0Êt”Ü\n\"™1fş¦³9e(igg˜o3ÀA_ŸÖË+ècÎë¨b†Ğ\rÃšH=\"„<¤ HKB	p<ÂÃpŞ°\\øb½®P¹ŠØÄ£,ÃÈ·¼ì°Ì@ m\"B*Èó:£1éÎ\"…©ĞÒ6B´vÁƒÊ3È²ÃÆx™FãpĞé1\0‚15K˜óÂiìŠ0Èáä–Ì#rBHË3ÈãœÔ1MˆTÄ°CS®ã‰@t&‰¡Ğ¦)KÚcU5±¸Äğ\rˆãÏÍC¨æ;ºMãÎ6I«Ò;,¢¦)Á\0¨7Ìa3ÊÕŒƒœÈ+a\0Œö\r£ª5A	èä·°“¼Û1¦’ı(2UöÌ7¥’r¾š -^Ç²²–ÇŒ1ÈÒ;6ˆ:İj1q¨Î9Œƒ\n†Ík¥Ô”Ác2Å±¶ËbŒ#u–ÏB«X´‰h@ ßK¢8‰#u9'QÔ`š&Ê|8>TÛÙ9€Ó'• x0„Dœ3¡À:Ğ^ùh\\¨\n=ƒ8^æãÃÌ„\$!xDƒä–™(¾1#NXD	#hà±¼pèã|¨ÎÏ\0èÔ c Ş'N`@Å„¶|£³âÛ™(äì±he® *zw«‹cÈ@Ì:J9)ò³\nó€(»vá¿îa\0P©;½ƒEóK*µÊtœ'Iâ}u((6=7iã†#`Ğ3ÊJËŸ\0…-rgZŞˆ30†Ke²»Šx¦*2	&@Ã\n8Ş¥Ö‚ËfM+‚×0¤)Jc‚áÔš©mØh%sKº¹¬lhó†%¤-Î:¯¸rh,ã¨@1ß5v7£sUæ3 ‚ˆ˜J9\\€#@ šˆciÇù²`äw×r{&} ·Š]\nI¨OˆõX.—ŒAt=€Ğ”Ã©-J©]>1@zmˆõ÷¿BU  CM”´.Æ›_¡Át”&¶Sšr,kQ\0õ†^¢i{¦¸´\\Úsn'œ½œ˜¤G¢¤::D)‰èN’¢VK©•ì™A4Á¤æÂæ“CzP_\r&†ÀVÑJnà(²qŒyNÏˆÓâh `SE@¹¾P@B F à†àÆpˆg&‰–Abr©´N’#\\»ì|T•Å’¦ƒ»¥o„4¸¢‚ÆJ	Q,%ÄÁ,Bb\\˜’@ @)å¦sÌ®#ÏAğC— Íù==F†H’¶S\$Y\\‘Ê‰¸€¢ÆàÏø=HØû¿Ú{4E(5ÁS\nœg©r¸ÎÂ\\Ck%¡ˆ¯2Ø		¸¬fZ¤ãÎÓeDs›±q¹-/K¡vT`º˜9?õu‚’,0";break;case"es":$i="E9jÌÊg:œãğP”\\33AADãx€Ês\rç3IˆØeM±£‘ĞÂrIÌfƒIØŞ.&Ó\rc6ÀÏ(©’A*–K¢Ñ)Ì…0 œ¥rØ©º*eÀL³q¤Üga®©À£yÈÒg«M‘:}Dèe7\$Ñã	Î` L†“|ĞU9ÉÁE\nè€Ìa—J°aÔÜaO„ËlXñg7G\ræè¸‚‹H¥Pb§œE@ÓR˜\r1¨ÄøÍV4™\"²H±³\ns:Éî‘:É´Ë\n9‚ˆÆY^ ò 4WL ¢†}‡¬5ãx(¤e2ˆæ[©”èra«xdÌü›rE¹+·}†ËšCÉõí¦Y·lß`7t¶'#\$œ@côœ½~Ø™ÔÊ¡9ë…!£ @1+8ÀĞBÎÜ(¸­„¨^p¸bâ2k˜Æ4˜e›âÕ\rÌRá‰ï¢Ğ½;ƒhÒ:BÇÄ/ˆš½C\"¦ƒJÆ\"±‹`7<QˆÎ9 ëĞ‚2\rL3ÄïÃ°Ò½Aˆ´z×-“Œ—Áp0Ë+£Ì¦Õ-ÀPÂà¿@P\$Bhš\nb˜2xÚ6…âØÃ=Œ\"è&=ãxë\nÌûb6\r#ÓìüH‹CRæ¸)¶ò“U\nƒxÖ´¦)É¢Ñ\rP\\\nÏğØ;1ì£)¥b6ÄŒ2{2µÃ2º6¬c˜˜\nkRÎ§rxÂ˜H8‹¤É@Ğ0ŒQô^ã1¬b0ƒ2L¤ËJ#‘«8\r/Óê#+êşÀÔR:.6»;\$PÜÿ%É‚ä´ƒKN1Œ0û'QÕ%†˜	«jÎ”hæ;«²ğñpI%N1D3¡Ğ:ƒ€t…ã¾D8Ñs(9ÊèÎ­c´[!xDµ\$ã;­Œ‹ë`Ü5„Ağ’‘£í´ˆã|Ë#\r°èàÀÏ@šSièŒ¶é£)\"&£€0·êƒ!l¢j²ÃÖì[¯f¥´\n@ ‚é\"Lú£BŠ¨h¹hÒª*n Ã\"Xzzt‘1Uø‘Öc†«Ñóğ1ºN¾%:ú0ºÏ¤&€)Š•¢M[¦ˆä¶Hü}S¹ã ê=_nhš&Èª+ÎsÉÄÜ-åóVl´cN„?Q¤EĞBÍûğl(áY8ÉL¯„˜¢&/ÑòA-0©·õÉ’êKò6 ´«n˜@.êÖ¤K\rLç ÿ’p7®wÜ¶üÊ\råQbJÿÀR7¡¥~#¦´gŠ‘iIë‘²`óƒ«[^\0€§’¼”ˆû6#\r8:‡êçÚYŸf\0˜sæ}QÔ.\$ğÀõ¾W™ör¤™œØd}!Ô6ë4ø*ÔC&Jˆì–wØC3Âéu©%ó+\"’d&ë†ÀVÔ@cn‰\$;eX¬˜A'3§„“«\"`óO0mMe* @B F à‹ãğ\\Ê¹0J°Mı>fåä‚¤`‘Hx´Y\nå#ö‘ôfÚ³¿-f0×ø¾ az'ÁÍÊ\"PÒ¾	Ï1‹ùmuóI¸²­x’	=ài@\$,%¬U›‹Z0X½3àpÊÊ‘'‰ü7œfÅ3¬Œ°œáÈ˜(B¢¤„€Få\$\"êÏÃ@:ÇYttXy]Y¥ÅóÎ°„`åÌ'VjrM–Òb´\$ãñ›hî6ƒÄ™\0";break;case"et":$i="K0œÄóa”È 5šMÆC)°~\n‹†faÌF0šM†‘\ry9›&!¤Û\n2ˆIIÙ†µ“cf±p(ša5œæ3#t¤ÍœÎ§S‘Ö%9¦±ˆÔpË‚šN‡S\$Ôé4AFó‘¤Ï\n‘›EC	ŠOƒÓÄT,Ì°ÛŒêt0‚Š#©ºv¼GW†ƒ¥®2e…Ñ†S‘K \rGS„@eœšq·:éŠk\0¡^\rFºò<b4™Dã©´Å] Á®43ƒ\rHe;d²Æ¸lˆÂe3ØóİH(…`0œEiyÈÖ ON‡zá¬R\n#™MæÛ™Ò»y&fœR”pt®]t&ºMZ½œğ@Q0ß_ŠæfPQ+lÀ¤èX@î!¨cêÍ7cHä5?*Êª@LB<±€PÜ7A l¤@BxèC°Â4¬*V‚Bš¸ÅŒªêô4ÁKÈ¨à7\n3gB(Z•±«‹z†‹ĞÊ™!1°¦Í1‹Âl0Ê3d)¤c\"ó,\$‘j¨„AL˜tAÉ¨Ü#ÍP\$Bhš\nb˜ª¡p¶<ÏÈºú¾èâ\\R\r–‘°2 Èî8£c½Æ£´kí@ÚÒ¡\0†)ŠB0X•/‹ôˆñ¯:Z®\r¡‡()«™@4cdû\0¥)xÜ±ª2j)±‰Óæ÷ ã„— ée&µ²”¢İ\\uĞŞ¿Ya~3¯ª¥68S«\\ûQTÃ­PüÙ#*j\"²£Z¸93:\rg!nâÜ›³c•9dºƒ’Ò<Rã,4\\ƒ0z0ƒ àáxïƒ…É%`9ÊàÎ±€ğÅÕĞÜ„Ağ9TC¥ş/ŒIÜ5„Ağ’6+¸7à^0‡Ó\"îƒ@Şƒ½p”ªITkI§¹e¾¨)ëk4±#FTrºEõ‹Å¿D[c€(húK(º\n@Rš¦ìÍ&%­µ.ÙÚk‚¯mğ½‹ê*¡ÜˆXÃl1ëĞ)Šm\rš%4›¯ÈØÒ™0ª‰¹Ú³üY½[Ğ%É†î¢i;ÚR¤%C@à¿rèèš‹#}*1Ê•6R7¤–t(¯˜¢&24–ña\0Œ*k”0@“%c“ŒÏ•Jıé±«>Å.ã\r`–µœ£[B­µ½zu•h©lüÂ2ôj:|b×nI²‡-F/Ó1C\\ã‡Ş:ØGT\r\rÙ*+¡Ùİª\"ÎC¨pKF-˜YH*·&J8Æ¥¸&”L±£2Gıô 2†	:EK²\n4P`É¶cŠÀÙ«T¤Iy“n)}& ÃR˜Ó)Ë6Ğ©ğ8X?N–;Á°†²fGØl ¡°¥»¸<cP£</ÁT*`Z¸nlÜ…rjÉŞ)%<iƒ•Ô´MK!f#n`™òzeÌ«ŞzäX6¾òòú_é=EJ)Fóöj•\\<JĞx»Är_Ï\\„„h²5R}C*,\rdx6LIê|\$‘vMpÚW	L/(N3#£lÒ	š_*l©+ –«–ZA'1„­F¸IQ*'#‹‰h.…L©<EŒ²—@¤jC«î*†ì—†’";break;case"fa":$i="ÙB¶ğÂ™²†6Pí…›aTÛF6í„ø(J.™„0SeØSÄ›aQ\n’ª\$6ÔMa+X¶QP”‚dÙBBPÓ(d:x¯§2•[\"S¶Pm…\\KICR)CfkIEN#µy¼å²ˆl++ñ)ÕIc6Ód\$BÓ!ZÎ-Ö•~äŒ„Ø,V}–'!³Ğ•”šl†·ÏUUiZ¾B@±ŠqA´©ˆSêp•ô2íQÇBÔùšœB#SàğëT­Q:‚HTÚkí“ˆN!([îÉ+†ª­ğ{…r ËÌ0ËJæ¥@Ö`4ÊëÌ–©¨ZlëIò¢´ã¯•ø…Ï¸¨ËãáZ¸šÏÕmˆğaRê#MgÑÄV’Ùv¢¤¨J–À9i’:A§Pl9É:Àæ,iŠø-%LÅÀ+¡^Ú\"Ã\"RBàHK0ÏÊ#*87\ràPH…¡ g†«IXÅ,*ú:…–.LnS£¨\nÎU\$ÎARÔ1ÌB£¼H”.´—0Sà•¸L;Xµ¤%B:Î¸-QäÈ’Ë*ÛPù4Sx°Ërğµd¶Ü¬ëç 9Hj’BCª{rB·)Ìò¶\$®á_DÊ@³K<ôİ2Şô§dB	@t&‰¡Ğ¦)BØóQ\"èZ6¡hÈ2-+\\\$°¸jŒ‰,H®{9ƒbÒ†Õğƒş‚9Ò\"T’8p\\’É©S¡¥Ë3J6\0¨7c(Üb˜¤#-6Sv»(òxU\$µ¢ÎVP«©´,ë¸ÅİvSˆ™¥e’ÊîK±*xù&lc=¹›v*7ÂÎ&×Z „áLc…¯	Ã\$«Ù”‘ª«¾Ò+µÖè8Íf/b=ÊŠÅ~×è\r‚åBhÂ9ƒ(äxæ;ã”<2€Ò9£%!\0Ñ ÁèD4ƒ àáxï©…ÃÈ6#vdg#8_kkãÆb7cHŞ7á@:#Î2šP¾1\rƒÜ5„Ağ’6`Ê6ÚÃ xŒ!ò„9„\0è4\rğğè7ñ#\rª\r#¦i™\r»·Klõ,Ğ,°‘?¸CİÏ¬—kHS›HGT¼\$V#ÚÖQÉÓM.…\nR’Èrş´b@ä©©\"	³jµº´LÏz³O:VãÒ(•‰ x£lÔ2J’…–˜©x1Ì3ß´4j{=¦PúÎY%¬Óƒcü«ÎÆª°çğù‰ûªO¢\n#îü’=³ğ²\nrí&å,³’tÒ½\nI50¥aH¾\"~çÈo €1·`@[èojÀ¢† ÒÁ\0S\n!0`Òß\0v\r-#@¡	r\r-ñœ¹g+Co\r¤m™f¶…P¹\0(Ã`XeÒt±C¸±¯dÎaÍ\"’{qDŸ?uÏÏ©÷Dé]\"Fó‹Ñc/§°å3Ã@aˆÀ3@äa“~mî!ÇÁğà1ma 4ÇÀÂİ+œ‰…¨û˜ù+¤iŞ+æh¿ ©\$}ˆ‰ø4ÂĞ´’ù!&XÌ‹æß2D\n}r)DˆH^,OQ–VD©\\Jå‹Õ(: Ø\nÃl\r!Œ5‚Ï	!ˆa\rÔ2¶· \\hiÁåÊG8¡|Í\r¡Õ˜\0Å3ZÈ \n¡P#Ğp°cš!¸3¹ÄFIHh·,i\$K)ŞOg“Æ†0ãiÉ9ŞJÊÌ×TnxÒc>\$9I3Ô‰ş*eØø£\\d¤‘àzÜƒ™ró‘3üXqĞúBŸ„a[ğ˜?\"|UŞbF¡O\0¸“Q[#…­%¸&B ÈÖC8\n§°h4\0A dª\$YÏƒúzÍä’VTA9çˆ}ÌIÁ§D­9YêrL\n—.•h½šGĞ‚Ø.ªY>!ª)gÊwŒ4šM›À.@";break;case"fr":$i="ÃE§1iØŞu9ˆfS‘ĞÂi7à¡(¸ffÁD“iÀŞs9šLFÃ(€È'4ÇMğØ`‚H 3LfƒL0\\\n&DãI²^m0%&y’0™M!˜ÒM%œÈSrd–c3šœ„Ñ@èrƒŒ23,Üìi£¥f“<Bˆ\n LgSt–d›‹'qœêeN“ÓIÎ\n+N³Ù!è@uÁ›0²Ó`é%£S#t„ßTj•jMf·B9À¦åCÉÂÂÌ0#©ÈN7›LG((‰³’™iÆŒVğC4Xjë¬h…n4ï#E&§a:‚ˆı]ÏV¿5œa`Q¦\$ÈiÚm°[ÔM7Ó¨Â†A€¸8†ì_f4³ëƒ ı¿©“şÏ¾ˆb€º¿Œp:Ã(Ø:£cª6Œ¯â(2xÆ€HKCÑ4<·£pŞ¨^qxb\nC*f†Fí°Ü”%Id:a— P¨9+/Úô?ü\0007Bœ%:9H“|À\nc¨Ôá#lëªŞ6Â€Òa•Z5\rêóz\"È4PÛDa0#:J;Îª·6ãËªDPäæÙNÈK>§O%L÷7:£šn MCrù(‰@t&‰¡Ğ¦)CPÔ£h^-Œ5(Â.·Î¨ÆÏ®ª ŒÊ»ˆ­\$ˆHÉV:­èØ¯É¨d\"÷\nƒx×„¦)ÊìÚ¢-(@Ä±iğÊÇUÌù7¥Ê²V6£bŞ&ëˆ@Ş Îˆ@±Si„•ÖŠ;Itu{zA(ğ8:5ÈXW÷[ƒ!lÀ©i†PT 2»ş\r/óUCB\\¦âkì+Oª@;¥påõ~7ƒ \\‰ĞĞŞÁèD4ƒ àáxï…ÃÉ#®£\\•Œá|3£3pË<7áv0S°é™éø5„Aò88\$Ãøã|Ğ¡0Ãô7Ãƒ ß96º§5Ã”„&â.’ÎÉvR¾°¬i\$¹®ª{4ÎihH \$\nÈê8#Èmr„…\nˆS†„êãÎ9£ÃrD’4xkrİ¤ª…n\rªzä£ªcR“:¹ç?Ù÷U¤Ç;>½l‰¸‰\\*8 'Šb¢v°\0Î9/ŒÎVê##Ã1ÖöŸuğI7x£ ø7m„]’Ä’6½2¢“/ˆO¥UÉŠıäbè‹çÃŠ	¸³ìŒkã®\rùõÚK`@ÂˆL)¼³“ ŒKÉ+¨ô§‚`X)†KÀªt*…ØCûQ.)Æ\$@ÚŸÉÄ(XT»x4*C¢z\r­¡Š¦1S`¼%a<B„ôDx©Z°¼÷ÂÂbaË‹€\\j5nàèüÉÉB.xú\$BÎI);mn,2vMIws€)6ÂÚjS\\\rÍ­”çĞ`Ãq+GTó¾ØÅÊ‡)‚&¡ã’D\"â±]Æ8ÃM\raJŠ…gœ˜§õ…!?ŠşH‰œ¡ô.^Á¯†ÀVÌq/æ89¾.À\n!Å>‰L—©I°\nÄõĞ‚œ‰)T*`ZÚ«<åô›§\$Ü€†ğ1Hy~sPìƒd¥ÆÈMÚÙ\$%‘ÓC’H’•a”'QhÀJx~mƒ\"ª,Ù!¤j·Ÿi=fIè-’\\MHˆ\"›Åe§çÃ<Éif¤•ïœRn©€(ÁÉÍ&CŒÌ»äâ„Éxùró\0002ÌbØs@T;“ªìÙ§³aÂl3æ»LÓ4Ia¶F¨å¾C@­Zyd#AQ¾˜\0™EÍêÁ2BB";break;case"hu":$i="B4†ó˜€Äe7Œ£ğP”\\33\r¬5	ÌŞd8NF0Q8Êm¦C|€Ìe6kiL Ò 0ˆÑCT¤\\\n ÄŒ'ƒLMBl4Áfj¬MRr2X)\no9¡ÍD©±†©:OF“\\İ†¼¤ÁQ£)’’išMÆ8,©Bb6fâéæPv'3Ñº(l¼Şï·óTÄÂ(=\nipSY¦²r5o’¥IÌéO™M\r‚\nµbµ\\›‘¥Œú~ÃYËåJÓÖÄS=E\r ¢\$RE «ÁM&F*D°•Œ¦pTLr ŞoúƒÑ„è\n#™d´A„L :ÛÑ¯%Ş‚´FÍ%óMVY‚‰R‹æ£‹(«œ7 ¢)·;Ş=„-)†M\rI\n®¬°(Èƒ&ƒ A\n\$PH»,CpŞ°\\Øb	k æùÂ%ä4\rì\0¦7Cb¤\rË3bÊC\\X«.xšb–.Ø¤ÄHì¿°KrÄ\"…£hÂ4°Â2=Â¢(9ãËŞö—C!ntRŸ\rÒ°–¯’„i²L–Í¥\\È28AtÍ2T˜M³|ã9ÏTï<—“,â\r¨#Ô5Ğà8Ckf-4ˆò.…Ãh\\2H,ëå\nƒäí\r‰(”ËNàäáKFSªó„¦)É€ô7ÂÑZ•apA1Ğ#\$a—CPäÃ?Qàä¸{.ß«‘@ÆÓMË06£pÖ0¨B’2hPÖ›\$95Ã„ë*®6.ÃÓ…i¥Ü~„#*n&Û6İº…)QøÕ)`@ÈŞã£ğı á‰c.OõW!•5rQXIòòº	«ÛØæ;Å“ ğ8\r*@É_‰ˆĞ¤ÁèD4ƒ¥…ã¾tIãjÔÑ`Î¡ ğ¬×Ep„I\r=v™¾¸6ĞD	#háßã xŒ!ó‰)1D§Nğ@ÇdÔ”…\rÖxœµ-–›2,›ç¹­h+†<ÏìN¤·°CBŒòöj\n@¡»1ûÊÜÜŠcsRÉÂt'ÊnMëÛÃÙÜ\n¯O¬µğœà¥=º3ÏÕÀ¤‡LZo)LÚ-q¦\"€)Š‰€Èç5ŒF[\rnöâ9ÀãõŒ©J2Ê`¡\0ì0ÊªŸY2¦¿ªÕÚ(Ë&âÈŞ:„ªºØß'û£ÓØ\nbˆ˜™Iƒ/¬4¦\"0¨ãÚª'«5O¶²”ƒ‘%™S´4õ	BÇğ–(2sÜ(*è:(2˜€Œñ› éaS±LwŒ™io­3°‚0ÕØhp‚E<2¢Œ˜_+ç‘y¡f@r³ù]¥´7¾ÀàğÊÉ!\r§ôÇA”,A6(¹G(_éßŠDÉ‡“´‘´Tn1XÊ°åŠsˆ1ßX‰i)A3:XdaÅµ–jœ£Š‚.:”“.Ÿ#”|n1ø›„0è²ÁXc(q«‡ºõÃ`uM¡B†@Òœ4/ä‰â”ÃKP \n¡P#ĞpY-Aœ› Â,›[ZE…¸6=xğå|±€’Ò['És\0å™–Ät’GŒjC'éª’òb[‹¶Q`9B‚‹a+y\$ ğ…#à(Ci=Åèó÷›İ”Õ‹NR%½vÍ‰)EiX:s(¸È˜³\$qñ‘DP‰º)xf\0:0ØˆÏHL}òX7Jü)H ëJäY/¨½U\"Ø9Å†ı\r•l^“±}ñ`–z°z>ŒY€¦³.ÎYD‰ÊLò)4EkEÁ¸%\"Ş\\@PO/Š„8á-G]‘š“Z)P";break;case"id":$i="A7\"É„Öi7„¢á™˜@s\r0#X‚p0Ó)¸ÎuÌ&ˆÊr5˜NbàQÊs0œ¤²yIÎaE&“Ô\"Rn`FÉ€K61N†dºQ*\"piÑĞÊm:Ïå†³yÌßÎF“œ ÂlˆšhP:\\˜Ù,¦ÈåFQAœ‰	ÀA7^(\n\$’`t:ˆ¦³Xİe£Jå³JÌë’Zå„¨í@p™ğHSœh¬ñiÀ€ïÄŠgK€…“‚‰SDŠG2›ã›CH(ˆa3RÎ@¢)’•b4:=”ãœ\n&Ê«=ÔW@r6Î#¼ÃwK1:ÂŒ’ZUĞó/à¡ ÄZ\$ıÆ ¢ğç#iÂ8\$\0Pª9ªZ&\r#j”´ ,¢4ƒHĞÙ£Ê´8ë01\rL[d²\r#‚~	ƒÚ8\r#’ò%Å°kÔ‡Ã0Úf9\r	T( ëÚ!+ÖËCCdo¬²3ÆOî½	Ğš&‡B˜¦cÌ´<‹¡hÚ6…£ È	pØê˜ÂpªH)`PØÚ6I8ìÜŠƒxÖ„¦)Á;,è˜\\Fğ@:¨‰ÈÌ±+¨ä¦¤òDw;“=ã+)|‡¥ãHAÁ	³`4ŒÉ`§NÓã\n’9…ˆ|(Á@,²*¢ãJX ±‰|ü7P¨È–;,jNÏ¥C˜î±=c(ñCUø@ cD43¡Ğ:ƒ€t…ã½¼(PpÜ“…ËÎ£·@ğÇ,ªø^*)ˆÎ2–¨¾1Q#XD	1XØ«££ xŒ!ó\"Şƒ@Şõƒ|;ÏÈÓ¾ğÜo*X\"±—e<©\"ŠŠN9Œ®réŒXL}<(	‚nœ¹Ã%2œ…\n8R¨ªhª¬9d6:¬(A*ç™XÎ'‰óZç_íz¥A¯qrüÀ#Z~È¥:–\nx¦*cÃ’‰J¦i…BC-M=6 hÈÂÄ»é6ÌÏ´+âH®\rÃ0ÒÅÌ‚–#{1µ£¨à6\rê‚öhâ˜¢&ÕşËP@#\n™S­?Ek4¥r#“…NS.\"¼«IP²ú:²]CÌ“Gbu¸½³Èâ(ı\\’,«,Lœbîpè´­o:ÆÉp,Ls9„“>±ò·ıæ›a´8á6ªˆĞÏ-·ıM£H§òimnoØaöCüñ_ÒÁÄ€‚§*\"½uè„„bK\$æ€€ĞK´\r€¬1¡PÆEJû‹W¡Õî½ÚÂÕ yb\$°#!GºVi3W`€*…@ŒAÄ\0p`ÇşáHR.Øç>BÄK8a\$I äÔ¼Kp\n}ïä2B’GEÔ»µr–‚MlCPç”¬¾Ò?	 nUêx„;¢öQ \nfäïœ°˜âNq`Œî*¡£X›a|1N*UE\$Z!ÊDì8ûNYıuM°ÖF€†ÍDAQÈ*Å€@ˆbĞs";break;case"it":$i="S4˜Î§#xü%ÌÂ˜(†a9@L&Ó)¸èo¦Á˜Òl2ˆ\rÆóp‚\"u9˜Í1qp(˜aŒšb†ã™¦I!6˜NsYÌf7ÈXj\0”æB–’c‘éŠH 2ÍNgC,´ìu7ÅÁFø‰œÒn0ÈDèÁĞÂbÈ%²Òe|Îu0‚Š§;Î`u°O”ÚRi67h§:M.ƒP©Uæ‚ZT4œ0Q¨öé“°›ç[õRÆuŠDADC\rš  ®\\JgH‰¸Îh2‚ˆUø¤R2çˆæS|SXi¸Ûj{)’W\\¹gv%	ÑÌï\\2ˆ9È®\\Æa=`6\$Ã}zJp2œ§&î®ÄX;F#fYhJd5@O¿ãó¨‚?™(H…á g\0†(sÂë«j¸Ü8­ˆ98Ë\\™%mˆ‚ì¦àP¦õÃŠ§(ûF¸‰ã8ÎÜ«BJhğ)Kl ¯c(ÎÒ¶ÑPìà ï€Ë(ø@¯Æí(Â>±ÔyH¨–Œ(Òv7B@	¢ht)Š`PÈ2ãhÚ‹cÌÄ<‹ P‹B´Z¸\rƒHô=&\$h\rd4ğÏ*\rãZ*b˜¤#)É(ì°# Ş\rÄ’˜\r‰‚\"1Jû!®QsL2ŒÃ4P¸4Ã9£Æ¨tZ\\uC\0ÉŒ¯JFÄU£’B³£é[JªÎéÛ´†:ã#80…\0Âê\"Èäš¦)ÑUu²47;4E\nPój4–‰©Ò¨9ƒšçÂ\n€ğÉZT`x˜\r°Ì„C@è:Ğ^÷È]&ªûÂ+ƒ8^ŠàcÂüš¤xD,Ã”N:^øÅHÃXD	#hà=¡à^0‡Ì@ß\r{à‹äcı—Âñº©lÏƒÕö¸á[U¶JÇT/ŠÒ¯¨®À„€(!ƒ¨à8+‹3(ÁJb9éŒÄ ±è&–Á:øĞÓF\n\n:0½5µŒĞ:¨‚B£k#~¶ŒÆ–Ã¢‰â˜©F\nTJ³3»Q³½»PAH¦5µæ4ĞömD7#ËxäìWû`,ã¨@ë¤ºPØ7É¶3â4Œá\0¦(‰Š› „0©¤#sà™iƒ–Z•£¨ Ú†OüÍ:¯TÛ~¬	gvöw¶4L›^\rT\"÷ˆ°Ş£GñZ#á	¹0ÒAvx¼«´Ç)Ë\r\"CYæüBƒxb1¥NLPéJı+6'^\nk\r\r~É‚CÅ²ãràşc‰\$%}Z¢ÒTÂÆN¤İ¡D\$R‰2kN¦”Œ8³\0É)ö=gµE€˜ DU\$eY˜¢¾QQÓÍ2ä´##Õ\0k`+i´1†¶“1a+„„ğ.\0ÚõÁsp°Ã†ÒPËKCÑT*`Z\n€nTHD3’Óì÷Qûi©	\"Ew@l\"Ë±iÏÑ+ò4^Éƒò-\$À‘’RN¦‹Œ?8Ñ-@b*´Ìñ¸R(tÛd~Gkdq\0‰2è}!»fA…ôŒ-˜vKNaŞ.!Õ:“#‚ç\"¡.“\r³>Òã‘šE‚&¼Ø•õ¾F!”iÉ@!¹´<Õ\"Ø\nc1ˆ¸„4û,ìı=Ë¸Ğmâù±z±ÎB‡C8EÀ";break;case"ja":$i="åW'İ\nc—ƒ/ É˜2-Ş¼O‚„¢á™˜@çS¤N4UÆ‚PÇÔ‘Å\\}%QGqÈB\r[^G0e<	ƒ&ãé0S™8€r©&±Øü…#AÉPKY}t œÈQº\$‚›Iƒ+ÜªÔÃ•8¨ƒB0¤é<sªW@§*TCL#‰i\$\nAGÑS‹,íÆ€A…€§B¡\0èU'NEêıÎ”TFĞ(H2j?wEÁ•ÎdZ…Ê¼Z¹•0\$öMŒ_Á”pe4PA£Ù:Î©«Qî¨c™/)@ªëuÚı†ø™ªkPsÚa\0M9×Ê—*y=J¬+iyê]JæLà\\Éd?mÊˆîG{Ú\rUT› åh4Dq_rAVºÑ´â>U#‰èN«¯#åÊ8D?‹C£íY_¹/Álr’j¨HÁ/²äA‘*¢^A\n¹f–ÁÎ[:\$Ó(à£‘Jİq9ÒP§96W3La8s‚\$2R¯#+l7\ràPH…Á g(†©éHNå¡DÅ—eÙÌBïãLåÁka[K\$\"ØE’±ğt%ÁÌE1P>U­…¹\\råÑÈ]-gJ	CG1_­¥*z[—g!vL#ÂŒ¤OI\0@œ…ùC!È¤˜«%KSÓŒ±Ôè‰ÎG—ÊòÀ@³nóÀñ<!ÓÎCHÂ4-5èò.…ÃhÚƒ\$DÆiIåAˆä!2B`PØ:Oâ™2rÈ\\§1ZÕ%P†)ŠB4ü>ñªşA¤1RS)\nÖ5ÍƒdƒMQ”#öcø]¥Hùn(ešLŸ')F™Ö-yØ-òA`–‚#˜5`ŸçI2H<ğ‚°ÔI,1ÑiSâ–)‹1vå½p@	u÷DâhÂ9ƒ(äøæ;ã”ˆ2€Ò9£ \\ƒ@4j0z\r è8Ax^;ìpÂ2\r£Hİ¡ÚHÎŒ£và<h#pæ4ûDƒä3Œ£¦¶/ŒC`Â7\ra|\$£€Ø2»ˆèã|Ú„ˆè4\rò è7ó#Ö2„Hé¢hCoÉÉë:Ï´-ĞÒ®È2òA/uBú³B€(3Lã<Ğ4M!z‡‘\nŠ™·ˆş2ù¾¤Skp»âzÈ³'ƒ¤êåfÓ\\)ò€¡R0Œî™ÎÊLtİËëİ,DıÖ¨ÈµÊQ!^X™Šöb«áu»ĞÂ¢Î?¤Şj I¸!‚Gu×–©)e4A¢L9Eƒ6lH†#2 D[˜0¢´èA÷Ñ«€,M{™0CxuÃ‚\0êãƒ{g\rX1Î˜Q	€€3—°ijÁ*†’ iq­%Óºh˜ƒxmÍ¡fØ›ÑÂ:GèDˆ¦DHØqd~±¡£¶&Å¡ãD±ÅT‘ æã¬kJDQJ©ã‘UBÜT‘6!Œ!v¥à½Ç¹\ráËW1˜\$ÆˆƒLQqî\09·ÇAÃ ah-ğ4™Jß\\(euªdNœ3/å©\\f¬L¢±5-åÈ˜!â<tñzÂ…l±½NGxò%£Ùb‘\"ˆ„5¨Xc\r¤1†°@ŞbP!°:ºG>æ\\èiÁåÒ‡GZ¢s¤\r¡Õ \0ÄéP \n¡P#Ğpˆc!¸3ºĞJ‘TL°A‰Ô>üæ”}#…ÅlEB¨ÓŠsÄĞ›Ibv‡<Œ‘ÒA‘.ñAI°¢S&.£òI©‘ä, qe–€¸'¤üs‹S\"dmAœT„ZK¤•-õNP’Ø!EE=˜O	ÃøºIzØDä|°Rñ‚¨m?…!ƒV°³P|`é],œKM¨¢ ¢Éâ„¾Ñ`d ›ÔÆ‘€";break;case"ko":$i="ìE©©dHÚ•L@¥’ØŠZºÑh‡Rå?	EÃ30Ø´D¨Äc±:¼“!#Ét+­Bœu¤Ódª‚<ˆLJĞĞøŒN\$¤H¤’iBvrìZÌˆ2Xê\\,S™\n…%“É–‘å\nÑØVAá*zc±*ŠD‘ú\r‰ÖŠL‰´ƒ­=qv¡kGZá)ZZgĞ²ä–\\;ËK’	XìM*dP‡Z\nFƒ&Rµõ(‚ °·©e1ìvASb€+aNÄÂ’¦s«Ñ0§Z½qO\"0V¼&7‘¯¤#ÊŞaÚ˜JÜ‘\n¾\rÉX!Nµf%<v%ñ•§bŸ¤ëB@‘X”Ú1ÛNƒrYû§’ëU*eÉÛ’^ê”J;P‹\rö›‚ÚÎùrBe\0u–DC\\:iyÔ[±\$ìÖ'QjÇ”	ØN‰rgAğŒƒ•¯Re9˜”Œú22E‚şN–‡kÆ#+`7\ràPH…¡ g †¯Yd@„é•EBbP.N!ÖP’‰8³®r‚.D!@vo¡ÖTÆeã¦Y¯„nv1Ë±+DÌiz¯«‘e'ÊbF¬LÇ\\ĞÊ–‘pKLs+G?/„tˆ-KbDLŠùX9çS¢çÑÖ[Ò>‹cÍD<‹¡hÚ6…£ É¡p‚ÍH“d¦F–\nÓôş\rƒ å.¦“¤¯“vu’	˜†)ŠB2CKä	`E\nOä¼Q¥[TçµŠYÕëdvŒI\nË¥Sc~Ó¡\rKVÖ¨7%ÌœÅ1YFFZ	ı§UĞ“Qm]öõaXü~Á‚hÂ9ƒ(äXæ;ã”`2€Ò9£% !\0ÑÁèD4ƒ àáxï—…ÃÈ6#vb£8^2ÙØñ†Ã˜Ò7ç¡@:#Î2™0¾1\rƒÜ5„Ağ’6`Ê6çƒ xŒ!ó^kƒ Ğ7Æ ß²#XÊ\r#¦!‡\rº–º¶³Lã<¨´-™.ÑŒ¼(	ƒ2Í“ÌèPª4²Êğ…Í‚H!,[pİ,ˆòÎİÑÇcˆ“'<sBƒ¦jB•=°\"d›Ø%)x\$Qf^ tZzŞ(\ry××EÖS1ø¹xB€)Š‘Ei[?|tPB-eb¶ó—´k	åV±¥n¦©ûçxv”Œhu--±Ü…'<â¥K}ƒà]Ş¼,ã¨@1êA\0ë¬ù@ÈCià€)…˜0ik@€;–B‚£†jHÀ4µ†*Ü›ŒA¼6‚\0æÃƒ³72h”øš4Øj…\0œv®pŞ¢DLhÔ°± 	|K;g¬·Š¡4„‚¦\$æ'„,+p.à¶ˆã)Ä‰\" ùÀ;uı°h0„#Æ ƒL\rkM,9´vÖşÃ aa­4˜ºÒ€e^\"\$8”£E)bvDÙÙ)#cyœBÌ›/¡:IO±ÂQÇØP'{»uğÆÄ(œoBº\r€¬1†ÀÒÃX hşØ[{jl 4†`òÜ¡AĞ)·†ĞêÃ\0bmìÔP¨h8ğ1Ê@ÜÊ\nƒ Ñ,N’§ç“X™/ñ\rG’ö_QšéKÉ€Nrl;	Á™¶#Ø’vMPˆ‘¨ZêtÅ8<êLvÍØH„q—+Å’t9„)ÒXL¡‘š†p>Ÿğmb­¾0´„_0ÒYğIÂN:!`ì“è³®zD( tÇ	¬Ö¢áÖòŞÑû3É\$¤¹œmôJ¡)5'iÜ\$@";break;case"lt":$i="T4šÎFHü%ÌÂ˜(œe8NÇ“Y¼@ÄWšÌ¦Ã¡¤@f‚\râàQ4Âk9šM¦aÔçÅŒ‡“!¦^-	Nd)!Ba—›Œ¦S9êlt:›ÍF%!Š¡b#M&Q¼äi3šMÒÊ9ˆ—ˆ\r†SqÒ6ib¬ä‚\0Q.XbªŒ'S!¾;¹İMf›0€ìi²1¢B„@p6Wã¦ëBÎrsÏåôJ1Î‘J¦ŠÆ‘ÒíJ´ˆ#±H(¦k‚TjzR!„èaÂ¬PMD4¨e”ká¤C±”Ôe×Ö¦À¨¸Öl®‘Ì¦óo¯KÓ` tø (¤e2IóÕyvk9ÏRá¥>AÃ^Å_æ°Ò…^Rj:›Œ÷r…<v—aıÆŸ‡ÊT®®C«Ä‹•î™B®„£ @¸/Š\0Áh Î‚AğT:- Pò2¡£pŞˆZqj	#*Š®ëøæ:N²4©+¸˜•A¨h¬2 #šV†ˆ#¢®ã.â(Ø0ã\nİ!ÊH ¬°0è¯F!]/¢®ì:¢›Ë4¢J”­,²€œÁNC\nÚ…ÀLÄPÌ…œÁ46sZ	6ÍóŒÍ:MQZÒ’;+„b\$	Ğš&‡B˜¦cÍ<‹¡hÚ6…£ É'{âù´²†5£¡\0È¤¹¬bF\"˜Ê97¯ràG‰Kå3ˆb˜¤#4Cz4­²\0„0Éšö¾ÏUÌ«\r£z_Q¹‹\"mQ3PÎ°I ”b•i{]M>LPÊ\rÊJ£Œ¶K ÓCãBˆÍCXó´Q‚<1[\n0ş¾jIU=öPA	ƒJ”ÿ¥ğ`Î¯¥2CÚ›?‡o°·æ5\$uzÍ†?ÏÖ|%I•H²7:LËUrHæ9ê´2€Ò9;@xØ\r€Ì„C@è:Ğ^ú\\0ŒƒjµU…Ê°Î¬š`ğ³\rÉHŞ7áDÎîp/ŒNCŒÂHÚÄ\\« èã|Ç„hè4=UÎÚ“â£U¥jÊ’	ÊúÂÄ1CN¯,\r‚„/ë\"-ˆİ£˜ĞŠ@ Ã±\$»Æ„Bt¤‚¶^§Ë3:V”?éj^±##Ğ©­,:ƒ‚ÎĞ¯mÂm…hJµ‡±é ¡’»#¨Î\nx¦*TXøİ?„˜ê•õn2ùoÙ•/hÀ\rhÀ@Î.JjšúKê*7ÊÅ°ŒoåıpÌÃ¥ŞÁw P²7¡\0Ç`«7èÜVhQ	„|4–2üTÁ\0F\nM` ¢T\n³v/„|9ÂªÕi\$JÈ3˜T„VÔJé)’ò\"FŠ:úJ°Œê2tC¨kF„ë%ÀÎM+}p¨'÷äı\0h\"K}1¢şXÚÉ/3Ïä8’úÛÖy¯k¡”’'’æË²aLq^,›D1Êsfe	—ğÚ`‘D[xQu—ÃÈH–HòÃQ!ŸFé\nIz#pÈ\0“ë\nœz@°‘;Çèî™£Ê\np’4„Xc`!Œ5‚¦ÿƒ±,«~%¬’jƒÉ„„ö­ğÚK›°bb¸P¨h81¢´É\$‚ˆÂ˜…cqÔ#Äb^‘ÔÛ.¤a˜„l×·ÀÊFÉÌ6¹OªÆYHÛQŒ0Ãx²€¾+á©š)2AÖ%<Õâ¬WÎËFro*`@J|g,Hº+§§<á±œ«„«¥ª‚.<§ˆ‡8dåÈhL€3€ª\$ÿVK0Z¼ƒÈ©f‹˜T†Š2‹“;0NÀ×¤À–H¨\"E%@Î•ÖÅMb)Equê–CC5Ë3•a–‹6i²%á\\4¡-XªF`€";break;case"nl":$i="W2™N‚¨€ÑŒ¦³)È~\n‹†faÌO7Mæs)°Òj5ˆFS™ĞÂn2†X!ÀØo0™¦áp(ša<M§Sl¨Şe2³tŠI&”Ìç#y¼é+Nb)Ì…5!Qäò“q¦;å9©²gÎF“9¤İ6ˆğ,šFl³MSR¡„Ãq¹˜GSI®äeÁa\$#ÚO7›#–1”ñD9×cª¡Î±Z”Q¤·èÊdÏañ8Xm(Ë23[,5\\6e*<œ\$˜y5âf\n\"Pç™[¬|È\n*Bä ¢¸ÂiÓ#–	œX;Ãp×3y¶k2‚‰‘ù.üŠd4-Z¬°äs7_ ¢¹„Æh:é§.9\$Ü—Œì›˜\rH¢ü7#ğØã‰Êğê;ª)[æ¥#ò•0¢E·cËv7\ràPH…¡ g† P´Ÿ¡ƒrÒ;!«ø)­I[M+-ĞäÃ#èÍB\\€\"…£k¸ŠÉ:.±NÈä89	úÂ 7îhÔ¡@B²…,qt42È²;-¬I’i¥3Œ4Šœ`9S*È\rÃ¨\$	Ğš&‡B˜¦ƒ\$Š6…¢ØÕCBè*½0¹ºƒr¦9££,|†·ã`è9Fhlß%¨/@Îb˜¤#'Ñh\\KOxè:£’„ËRv!¡\0Ş8+£,šÑƒ¥™ºI\"„0Œã\"j)¤ŒÊH#Hˆ>Ñü`Û!(úô*Â çg*3r—·I @¹×…u^AĞ‚Ü•%–ôŒT+eâ­Î˜ËRIªnòÖC€Ão¿Oã*<ƒ_U‡ƒ\nÓÁèDüƒ€t…ã¾4&#jÜ†…ËPÎ¦y#.ê+^%Cä˜¾1(CpÖÂHÚ—®ğÜ:xÂ3¶‚4\réKÕh(d\"GR2ßŸ¦«ˆË3\nói£µX(	†¬Ùéæû°£ıY\n¨S'	Ó‡·³b\$m3\n:Uun³©¨ 'Šb Z2(xr¥x:Jb¾»K5ŞÛéÚàƒ)O“WH¤ß¿Ômºªkb3Ö*¼š‹#z\n1±ãª\\˜%8pÄ¶˜¢&\rŠÌÆ8pŒ*lJÚ£ÆºxÌ¦'´ å7¦¢L%1É®3mèúp:9\$¾Æ{Ñ½R¤§d³òs¶ºXÕ9õ¨(Ğ0İQ µÓd¨©é}—6–ƒG]‹15	áÀ9¬t]\0à*8P™+\"Fùü€À°8†\\|Iòû'éd†‡w¬È)=,ô= Ê÷âhB,ô©ÂhPMB›\r€¬5‘âB®ÌªÄí¬ÛØ*HSŒçi9„gì´©%(ÅD¢…P¨h88”ô ‚j†ŸP IÄ¤1^ò‘+zğ©õC£l×\"ô`ŒGò’6GHù!.ÄuÆ)BÌ¥áCG`( †eÈLÎ8R#®¨İ˜ÚHM¹fÀ€G¦n®‰é2ˆ0í—r¡ƒPz^Åè(,ƒœ–8t<§‰Ú/™HLVj%l0ÂÒGã!7å„ß*'ÒIªš4‡ÙàÆ@TàEaÕL\"æt&\$ Ed-b97Ú”¡È%Ü)d‹‚	¾*r8™€";break;case"pl":$i="C=D£)Ìèeb¦Ä)ÜÒe7ÁBQpÌÌ 9‚Šæs‘„İ…›\r&³¨€Äyb âù”Úob¯\$Gs(¸M0šÎg“i„Øn0ˆ!ÆSa®`›b!ä29)ÒV%9¦Å	®Y 4Á¥°I±Àë2‚ŒFSĞ€ôm4ÇD(íXèa±›&Â\0Q)ˆ™€šãG“<äzFó™êî :ÌO4˜”Èn2™åv\\ë\ne¿Âƒ¡B§UâW‚\nÉÒ·5'ˆòt£ãæ³(œu6æ&3Ö@D0Ûô‚\rá†2T2Î©ÓKY¦€rßáôQÊoÜVQ3JyLšóyÈUÅl\rû)(‘Æ”›ÁE©ñ¼Îu5F\r7™„à0­ºŞüh(Ôş	ÏZ99/(È‰Œ‰0Â5€HKŒP˜è<ÂĞÄ4a–c¸·¯IhÁpHEAˆ)C«h5C8È=!ê0Ø¡¯B›Ê:?èÒ´€R\0<Ãrê{h˜¼!jz4°Â2#¸ÆÚ¹	ìŠ\"\ròÄhú%&!\nŞ6cJö„¤Q ®<iJ<§Â#,¤0Êp7Î\$æÎÎ³¼¨šÏs„å%Pí)¤I²°@	¢ht)Š`PÈ\r¡p¶9Tº½´‚(IÃ\nbK\rğØíEìÂhó	Èxà¡Ã#â; JpÖˆ¦)Áp@)Öñ\0Äü(T4üÆÏ(Ú:ƒ\nş…òSØÔMõ+\$;ÏòhÚ”iú›MéèÎ((\"\rãú:İhÄ4¨A\0 \$\n	°§(5å×>Œ!bÌú¾ğ#öLÛÒZ›V£-o,5ÒTšUˆğÊ[ÏZ<´¦ÉÂÀ9!n0æ;ÁÔPğ8\r0e6ŠÑÁèD4ƒ àáxïŸ…ÒºĞÈAsÊ3…è†’< ãpæ4Û!xD-ãÎ2™°¾1Z£pÖÂHÚ8\r‰r :xÂ3£˜@ì¨•§µûH:!lÂzÈ\rÃ¢l¶'!*Ë³(ûjb—r» ³pC{,=3ŠB_Wã—¦)šÓÌíØÑlL³? –¿L:Ë°Ä.27sÁ\0P¨*¾7wo#:.ãƒ ©³#[Ş[;¢r§©üÍ\r-àÄÃäHrî,¬óÌà”@ô“ö­WVóÖ0´6«l-à1@äÊœ„±Ÿ\0ÇÏƒ¨Ä³zDÏ¨³-€İã¨ÃpÌO¹aä5?·Tõ‚Èo\$©l7†ÈÒ¸ (%ô3¬p¢ÿl €;@‚ P‚A¹¤4š›Àio˜Á†Õìƒ±˜&ÁI‡\$\"ºªF 9‡R>€ƒÌ3†°Ş’–Ò>\r0áp:È„Q¡±P,Ñ»gÀM¯H.\0« È \0woå¶+‚ZäÕa+1Æì5/‚~KyqMGÑ–>bÎZ]aı¥•|?Ô6ƒªÌaôÇ¢³#Jb%‡Ü	—tx¬=rgÍü‘÷‚¢bòOOŒ‡Ée…ÜCˆ &N¡'ĞŒ™†Ñ„›3´Xk*¤|ü®P’Ä;.	Î(£ÆrÃ	I&ÁA¶ò¾Ş¤¡T*`ZPni„çrm(%TD ĞéƒÂd~Ç¢r”1|C‰¶Jg*xls~&¨I±ãSœäªnÎ¹o_	¶\$Õ,JÃhb#¯´:ÒËJ\"ji<PÊç&˜\n†‹VBĞ/ #ÈkÄ–AÆØ\$IßÄ?e*F”%¬úÊ@‡_Çil›Xri“PW7†¼<ÄÒF©š(L,1Ğ÷Vç„ãAÓŠl¡Éßã\nW…õÈ”#\nœ\r…7Õ²ÄhIÔ'H­Š­³\nŒ™BcaŞ§`Öò\\ı¢ Ô—ˆmNóÊ(3f¤ÊXĞ†Ã€";break;case"pt":$i="E9jÌÊg:œãğP”\\33AADæŒŞ aªDyÌæÃVŒ¦Á”Üv4˜NB¼¨âu4âàQPÂm0›slği6ÅÌ’Ó”¾cŒˆ§2ĞƒE˜L„è¬\\Ë?€™f‡c	èÒoÎF“9¤Üa6Dê²ZÁĞÊm&)„ç4‰&JüàU9ÊE€Ìa™JÎ°aÖp 2]­–ãt}je9Ò®àª}¤jÛ\r5™¡PÓÌ™¦k1¦‡‘ÅñgXÁ]L°£˜(ˆa¹ID³‘„C0ê¬à¢›k_Œº QÊoİ,|bfå½\"ß–+ùÏh¡ÛŠrcGŠní`ŠvŠä¯×:ãõ.•–…!£ @1+pÀP\"ÜŞ,À7, j„œ(°hPÆ„°IzĞ—·a\0Ø7Œk@'NSî£JÜÎe:ªõ\$c(Æ¯¬ã\"…©ÀÒ´ˆƒ+8¯‚À\$²­ØÓ#r®Çi:SA	,|’\$ÉC’™ÀğŞ0ÇìÌ²”¨lØÂ3¬N0\$Bhš\nb˜2xÚ6…âØÃ=Œ\"ëÕ0GOXÙG	S!-·£cµIB‡\nƒxÖ“¦)ÁÌ; cc\\™„|´•¢ã2Ä6£bT¯ªoÚn“-É£æ×0Ş‚£§Z&’\nD¸3’VöŒQü\\ä²L’l»6õÙˆ²Ø8Iv@X°¬;’¾Ë\\V¢Ú‘2ü ™&‹İ`4µ‘,bµÓtíx	«²Ü•°æ;¬Rğñj[Ã \\•¨ÑoÁèD4ƒ àáxï‰…ÎL['ArÄ3…é6:<2ƒœp„KŠT3Œ£¦/ŒUPÜ5„Ağ’6tøxÂ3A²ÀO|0ÒtĞèØIÄœø&ˆ¥¤44*¾C\"ocZ²3@“2ÌÀ@(	…pK–²\nXR’ -\nø9£ƒrb‘¦‚ˆêœ•£UŸ'Ê±oÄ¹¢;¤E»ÌÊ¶WKäË°‰]´ÂŒ,uao©i  'Šb¥àµIÆE-„;Š75X¤2£Õj‹®ZRv–¡#›­İ“!%å)şcxÜÖ!-ßir P²7ö1(ÜÆæ£{“ÛX£8@)Š\"c\rÛòĞ@#\n›s(&0ÈRàƒQ­Úhş»ÈRKObòßÖƒı©àŞ¾]¿“£ú?ãz–¦’Ic(dĞ&¿uØQ*:iÅX¶«u¼Ñ&Ø®5ÌEÃc%\n\r”–„C‘v¡Ğ46LËJÒ\r‡ÖS„ïOfnä­]—'HXœ+PË|³>„–ì ‰œ_!œÁ%T\\­Ãfe/É.´f¸ì¢Sf‚GíÀVÔò ˆ=5àæÃŞ%‘l´UòqI)â µ“@Œöñ‹håÒ\0\0ª0-qŞ—ÀÜÉ¢UïÕM8&Ë\$ü‚&eğ–(¤fàY#F¡‘\"HA”7wÊZ“bèª€QA Á Æ·ƒšº>	é*¥Ş³‹ûySÑv1©æ^Ï 3ù_©Z–…îJœ±,§,a‹¥P\0LyÑø3€©”óÊb„GDÁdı7²¡TÅ'xv«),\r£”v¿äq‚foˆÁ#T;‡,æfOS{=eì›Hì9¢¼E\n€`";break;case"ro":$i="Ed&N†‘Àäe1šNcğP”\\33`¢qÔ@a6ÁN§HØ†®7Øˆ3‘ŒÂ 3`&“)Èêl‚™bRÓ´´\\\n#J“2ÉtÀÚa<c&!¶ ˆ§2|Üƒ“ÊerÑº,e œÎ’9¹œŞlÎF“9¤Üa°0ÑÆáˆÂz“™&FC	ÒeV‰MÇAĞÂb2›³q`(™B·ˆ8#9–q_7œåI¸%êãfNFÙĞŞaƒà„‹±»%¥Íç59è‚äj“Ö!U´Ü¨i8f —,ÌØi¸g¬qC®rH\n\"]dò»ís`d&\r0}tÊLr0˜îĞpVÜám³hE#+!6e0‚ˆæSy´Êt±ã°õ¬qÈiáq=İ]é§~à¬@P Ğ+ïH’½I7Œíd8%©øÜ2¯pRÔÓo‹¨7\rã¬Œ\0Şí=àLAD0Ò#*'A jœhBFÁË\nÆ½.@P¦±/Îã£ŒPÇ/Lp¦À¡²ß\nz¿4l´9\rãpÒ‘nBd6=\"„(ø T9¥¯@õ5iÜÚ‹¯Ê‚ìî¬rC ¥Šøø(HRF¥D#œ2…3è8\\ˆ(°Í?/³Ê™Dô2Æ9¬CJnì¸ P\$Bhš\nb˜2pÚ6…ÂØóW\"è€'näŸ-JS|Â6>h–Ç\"˜¤#/£x×\ns\"4ŒOğ\\<”õ>³c\n4iZø1¢Ã˜ê1Œqro'²KÓü™>(Ä:¥ˆ•”Áë¼»6Îr^™Ü²òØƒ¦º…¸3İÄû1Áƒ@Ê=7lÄ8:¦è«ÜÑ7ˆÛüà.«\$0¢Ã„	24o @3 öÒ¸	£è9 ¹Pæ;¬Q\n± õH@\$ãB3¡Ğ:ƒ€t…ã¾Œ(Š4&9ËÎÂš€ğÒ²Rè^/­:g¢øÅ2\rÃXD	#hàÁ=ËØxŒ!ò”9„pè4\rñ=¸Œ6=’¾B¬&½â*¾DÒBŒ“Šä+².;\r+ûıAËÈLDÑê|-Ûo\"ëA…\nXSÀ¸Íj~ £-â?CXhçÌËÖkD¨ÒªÌ9ÙùHÔ±6ÃS”EÃÕ{Šx¦*q0z“§ôP@*RKµ°Ÿ ÌU	µËªd9ã›ŠF±Ûó±¹5£º”Œì×rÊ¸jí\r)«QÚBÌ:\$Ë`ë³³ñ\nOfà‚˜¢	Bç0“„`¨¸nD+(9ÃğJáMÈš´À…È9e+G`ê;´¹èAÍ“Á€Ë‚\"éöºt8~!)kƒ&Î‚—\nÔ|vdµÍ›Uf}ÕÊ\nqx½‘â@¥‰âš~/Ì4jUCy,n0Æ\"åŠfCtqMÌ£—æ¾`Òh&©M0…8Ä›”Ì|Eì³!æb=1˜6F5u	Ûx íã« tNÙ1P°¾ÖêII&6Š#:‚x`ÔbRûÈ—­#<H%±(!Ÿ\0Ø\nÃ¿a¬˜¹0ìZJLäºÅ)\"úÖ'6‡X>`Il\n¡P#ĞphÉ‡\rÁœ›¢päéà[fsAÑEÌin–LÊ¯YÀ‘r2nHá|MŠà¸Ésî[\"ñQÀü#Òä¥‘r`?'‘2\"¤V¥D*s2\\”·%¤ZÜ\"îüá/uÖgŠ°l]€€a\n¹_\"IÂdİ–RÎ‰y'è,¢`ÏE†:‘hô¢y2¡Ój&D4}Ô€£<_8z¢%Ô—£Ø—cBa«õ\"&©¤mË‘n¬˜wKüâBòháŸcñLV(t";break;case"ru":$i="ĞI4QbŠ\r ²h-Z(KA{‚„¢á™˜@s4°˜\$hĞX4móEÑFyAg‚ÊÚ†Š\nQBKW2)RöA@Âapz\0]NKWRi›Ay-]Ê!Ğ&‚æ	­èp¤D6}EÕjòÙe>€œN¤Sñh€Js!QÚ\n*T’]\$´Ègr5„ö9&‚´Q4):\n1… ®KüIšIĞ·hı‚«IJ–6HãB?!¯Àš([ö&	†æäsD5AWÊê‹¬ÅQcCXMe”Å1v¨£6PeÌ×:¾ÏC¯Õ¼Æši7\nìÒµå.,Vû’’Ô»´×ù:„ã,±[•ÓµŒ´7üË‘Üá»>Âæ2S¦jbF_#\$¢@ã/©šTõ:êq¢G£%t†9Òg¨BhCªk\n¬è>PŠ„›ˆÉ&†¹4'\0ÂBù@*,\\#%Ïš\n[’\räG¥OšD©%¼ıHqi?Â’J€.Î+\ró\0Ô(ÊPÀH:¸ÎÂÌŒ£r3s.¥‰~È·a(ÈÍ'I¨7NÔ9Íæƒ7;Gh8<Œ¨ÀÜ7A j£2\\4¦6’ô†GOK±x•ELqkG‚‡37Ry6¦©é¡?²ºˆíShléÄˆÉZµ»Õ?\$¤<ŒÊÕ9 MRÈ+jÇ\$(ELİšú†í)pk~ş9L±j¼ª²F¤d*¤•\$¬3¬(–‚|_-ÓÌÖÀ8Jy|&…º°ÛöÍÄÜ\\ªòñ:FeÚ^•¼åÜ/•ğ„\\×İÕ¡¥â¦U0ãr@¿æN\nI9ŒEå\n2–rœ§ÂÚîYäâëk:\\õX7c`è9DK:ˆ³Ì‘ºâ±Ö4Ã'C«†)ŠB3N»BÎÁz%Uî\rrgÏÒÓAÄT…&‰\"±«+’±±‚¸å]Är¦¡è3W/Í’Ax‚®Qz“¨.Ñp•#ï£Å³MIìê‰hÃšR¸ÖåÖñÕYôˆ0ªU¶¡¥ĞX¦/Ï/ÜòÏ\0äJPT¼¦r“áœ»[chí§RAò\".ÏÆ~¨Ë®Ö×72k‚£‰£æ:£@8xC˜î7SxÊ<Hä2Œš`x0„G¦3¡Ğ:ƒ€t…ã¿Ì# Ú4Ş(]æáxÊ7~CÇˆ7cHßúA\0èCg¡Ñî…ğÄn\r`ˆÚ`e\r¯Ì:À^Añn`‚	‡@ĞÓxt\rğp0†°ÊHtxï6À˜(\\ˆÙ¦:dœê•³²eÈÑo)\\˜›÷6vWÉs¬¡tC1£\rPi>&ä•»#È\n+{;Gá†š#H\nx).H’˜ã´ÊIÆl¥<”¶êÈÉ	õuÄ¨–ãráa»¯BÍé\rƒ~\\]7DEhœ­\n«L›–´6–Ú‘ÜDF®#¶tRœ¢[+®‚ ›qÃA\0P	áL*Ó’hé£J:FÈÆ¤ÈÜ0Œ‘§h«i-&\$úÑÅ5µ“~Ğ\nº•Rœß»ÒZŠ‰±h‘ÅLœÅóVˆ†)§ ¯¥uœª =DEŞA\0c €:Á\0ŞúAØA¤3‚\0¦B` Á¦‚\0ì^ÀF\nBp†äŞ`{Ì…P¦u ŞH»ÅÏ¸Œ‰rZºÉ9)&(á„‹åA“R`%2¹²/–”À(-¢E½¾¯B~aÖ%9T9³¨Ê§’k¶V0äÓ*\"dYÙGšÓ`4ÇA¦wÁáÜ0¼GüM@02·²!WÄJT*pÑÕßİª‰ç)*ÆvÂ:@4¤–Ä¢U*‚äªUa»ÒªêñB0jµ	¢·TØA'h²¯ª._Wâi_«±€¯9äÉ‘f9Nj'›B	…`m–¾J‹ÙLl£TÀş”p†ÌÃ`+p%5D¨f£s„_DĞG9w	™yñ;ÄWÚ.±ÄaGÓ²†ĞêğÁ\0b„ï°P¨h8Ì1ÂØË˜dgÌ˜%5o#u™‡‰5PåãtŠ}Ô¡ÚÖVPZ²7~é­›Çu«\$ ]	Òí´ˆ`_ÈñP[4&?•Ì‡-Ã‹”JÚ´sS!¦IPÚ°ŞEAJÏ™á6å†ì IOÚ™%5*]+lvjğÑ9èîÔ xáYzîonl“GõÜ`’Ø½#0@0…YÌ²Õ¬”!vâ&\ræ5„€ŒİB‚´®B2o†K²dà\r¯2ÔGşD“}õ½BÁ<^Œƒz…Æ]ËHäŒËuÙWÎNAÔŒ¬ˆ/xVq™\\]T}xovlY%Ú˜8§F–\rR™FäB‘’éäfƒB7§2\rmX\nVK5Äõq\"";break;case"sk":$i="N0›ÏFPü%ÌÂ˜(¦Ã]ç(a„@n2œ\ræC	ÈÒl7ÅÌ&ƒ‘…Š¥‰¦Á¤ÚÃP›\rÑhÑØŞl2›¦±•ˆ¾5›ÎrxdB\$r:ˆ\rFQ\0”æB”Ãâ18¹”Ë-9´¹IÀå0=#\0¨™¤ÎiLALUé¤Ãb¦&#¬üÖy”ˆD£	èòk&),œP9P˜jÓlóe9)”»\$ô  ›Œfó±¤Êk¦œê4j¥\\ÓY­™e%V*ûv0ä§ç3[\rR :NS‹9› ¢\$Âµ‹1¦iHË'¾˜Ì ¢¢`r±”óØb9”Şm2#Ü2Ô\nfmŞL†“¤Oo:ëuûòøH2ğMøxñ Ø˜cJˆ9¸b äØ?íº¼4¿ĞrÏBs8£IZ5¸a(ÈH èÃPä<<ÄÜ:5=£°<¿ PÜı„°\\‘˜bÀ#B~·¤Å(5ŒŒøŞŸ§¬Êñ3Ï[&%Ï` cİz¡hÚ0£àP2(Ló„Ù>Ì@Ê2<£Ğ|Ä1i\0ĞŞKê„ÃI¸@ÎÃ\nt^©°ØË-Kƒ`\\Ï#`Ó=ÌüÛP!-£ëÅ›¤3å?ŒcE#IĞ¬âRÉ\$£Óî\$Bhš\nb˜2Ãh\\-U¨ä.ËÃ\$PÀ,3*•°\"ˆ28ÎØñ!Ì‚z9B(ÌÇ?·xÖœ„¦)ÁpA:˜©‹Ø¦È˜Ú:¿å\n(=Eí‚S/×cœ¦+Ã(Ä2¿Éì#OÈ5ª‹ó\0ãuğ9¯\0Ü6÷‹CÊˆš¦éËmx;7C•º0¸Af\\ªÊ™`lS]àÂ”‰Ösc„cïó™•JJb +‰S™R_`æ;¢tğ8\r8%`ˆ²H2ŒÁèD4ƒ àáxï«…ËxÚ´2r&3…éÎÂ<2Cv,„MÂ:3¼:p¾18CpÖÂHÚ8'0èã|Ğ„HĞÚ<Ãi„eğÈKhÀÜ:%+bÜ¸Hõn±x İ	Ìò”¦QªĞá˜5¼š1”_İó•S”÷-Ì¼Ó\npR”Ñ8>Ä¹X•-¥©zb7=Oz:u)²qÖõ/ù[mC±†^Û¬–J9±ljœ”Îmn§«jÛŠx¦*!B¹z5»oı6(A^IõézŸZ™Á¬Cz´ff,ÁÔ’»ĞÔúZ)Ÿ†½â½°O€ iøVğË{óC…˜0¢'ğ·\0Œƒó\rÈl—/—âƒ1‰1«¹µÓŞ›Â&äYŒ%²Z^â˜Bì2¿0ÖV˜àiN°˜DpJt:8Dñ61!BjÊğa3¦É÷N¾‹¹)‚\$4E¢˜‹Øù'\r°9¸X,G°t\rµ7T‚ªlMÑâ=Bh´ (eE{‘²ô…\n½ÒÇè©#iD‚=;\0¡òWD(VHã@D‰{Íà<‡gôÔé)gˆ6°Ö¢ƒ[›zÄÍæ˜³”PXôG\"jAN'¬˜2n%!”Æò›‰`AT*`Z‘š#e 3»„õHš)Qê‡¢ÀyÔ:‰Qs`ÈÄÃo7føa=…L8–ï6b`½SªFo\nÑ:•\n\n…Ìº”’ÉÁ#„zP±…€°‘ô\$±mD³PèÎ˜Vrâ”‘›ÄÌÊ1I2H ¿Ä^n]IñM¯X¡ƒ/B¢ÔÂ!Çˆ˜7#†F\ngOà(&AƒÖƒ=4¦Î‰ºb†PÚúZH•Ê¹2ïQ#œGHÿ¨ñLS€	dt¥pŠĞæÈ\nnĞ¹#¡|ò^åÈºba\"§İe()Tcxd:n'‹tğ”";break;case"sl":$i="S:D‘–ib#L&ãHü%ÌÂ˜(6›à¦Ñ¸Âl7±WÆ“¡¤@d0\rğY”]0šÆXI¨Â ™›\r&³yÌé'”ÊÌ²Ñª%9¥äJ²nnÌSé‰†_0ÆğThÒg4Ç‘i1ĞÂb2›%â\0Q(Êz‚Š§ÕœÒ\n(§¦“h°@uº®Ğ– g››Ì’|T¦xvR)tÚ&§f›KîwS1Š¡5ÙM'»A;M†U0èuXD“Tœi¸ˆV	Ê\n&Ád[ò9”Şm2PùNß6İÊ°Z1Ú?5Ü°6|ö€Ş ¾A\\Sœ‚ğr4Íµ1OSj@ı­a8ß‚Lœƒ¤?1&# ¿ğÃÀ£RKûCÈÊŠ«`PHÁ i† P’Ï¡+¸Ö£Iât\nhÒJ÷Bbø’5‚ Ê›\rÊ4<=-@\"…£hÂÕBxÈCÈ2ºÀP¬ª5/;F7Éjğ”#`Ô»Š	ëV7(ª;2Ç±úÔË/*›ªŒŒ¿0µJ´É-!i0æïÀŒ,ØÓ|Ì†\rìÕ;GÍSî—I@Ô	@t&‰¡Ğ¦)C \\6…ÂØåKBì‚2=S“Ä\$Ç+Ğ±£¤¤69QC”2¸kŠ.œ½L0@•%²à†)ŠB0\\K,ü¬’ C246­Xä3è5°C¢^¼½#¬¿c…XP£Ö{í„ÔÑ£¬’\$Î8Ş3èK«Ešn£T‰cW]¯+Ú2¿.ŠØì­½•¢Ô )}^´½Jƒåi\r#\\à6¨\r86£˜îKãÃH‚ÒA\0x›(Ì„C@è:Ğ^ùH]#:*€ä#C8^ÃfcÂ7cL „U›z3¹9\0¾1KcXD	#hà6®@Ü:xÂ2#˜@ş\rUfïÇÊ\"HÔQò¡§¥â\n=1‰EM>ËD¹­.ƒ\"’Ï£%JÇÛ©¸ \$\n\0P¤(êLøÃ|¬7àIŠfš LûQA1œ,¬Ã\r3êéÉÏÏÈçVÌ7^/YÍïRqhT£vz\nœ§nşâÇ>ûÍx'Šb¥v-lCS?\0ñ¥T›Vğ¤Ë§KŞ#¸nÔvL,ã¨@1¡kş–7ÈÁn1+á\0¦(‰\0ÌÕ MJn#\n›êÅ¯Œ\n¯ŒËÒ9ÍI^ó¿OäüÜO@æBİSù9.d˜¢¤äÃt?g\$¤'àì“ AšcÜÛ“€äO˜I/zH4-\"¿9©i«§¬Úˆt\r¦–v˜KÂ›N(¢ÃÀ—ñ(JÁÔ5-ÒşuYF\r°ĞÁCceË1ë%„ù¿ĞÒgË¸S?-8£%ÅàOÌYÀ&)³\rSAfOñdş&èºÓbû”\"‰ĞÅÆC’SÌgŠQ>ÅxÚÆâƒAåÀVÉĞky0Xı²|ìƒ¬@F¬,#>F®d9h†à€*…@ŒAÁ…\rÁŒÅÎfœ)\"]iù:‡h.I^¢y\n²…ÎÃdÓ)e;	\0&U’Z@“š”Ğa…J©Y%!‹—r¡.6xÂÍ,¥œ´)~‹»PË¤F ¤ZCc=!ÌĞ£8°!úî‘n·ÁçdµHÙaF‹ÀÂJi\nOI,09D-\"\$djkcØ’yÏ¦®Fˆj5’Â^ z	*J˜l\$2Ğ©bÀS\n\r€€Á¥ŠYèK\$1*\nne©¹Câ…u<@‹£>-¬4\0 ©3\rZJ¡è¢)Æ¢J\0";break;case"sr":$i="ĞJ4‚í ¸4P-Ak	@ÁÚ6Š\r¢€h/`ãğP”\\33`¦‚†h¦¡ĞE¤¢¾†Cš©\\fÑLJâ°¦‚şe_¤‰ÙDåeh¦àRÆ‚ù ·hQæ	™”jQŸÍĞñ*µ1a1˜CV³9Ôæ%9¨P	u6ccšUãPùíº/œAíkø¼\nŸ6_I&…ÄN¹~]É3%¼&°h,k+\n²HˆÆD—RIVowƒÉ”Ù>yšg—©®Å	³4%¹ìœ´‚Uµ˜úÆBâ ´Zà5ûÅŠÉW£­i0IôÃA0œ®-yÛî®#ÕÖæmÖG\\b¯½	'hiàğE•öÆ¼‡IS%Öï‡¯Æ#X‚sÜhÈHI¦JsàåNªòX\$ŠS¬·¤…Üh’H’¯ìâÜÂ'Á¢í)¨#Løš\nRœ¨0¨Òâ’ğRrÈBï’ú¯§*ê{/Ñ‰ ¢;ê[w¸èèÈ‚2Q¢„²\"¢¦%„‘%H©ÂÀŞ¤šH¹A jœ¸­qj1Fl4%°ªY¶Q!?\r©¬iÌ°ñ\r‚fhÎí\\^’'*ƒ É9L\"HJ ¡s˜·jHO6JPˆ¨\nŒ\"ô©k±:©<NĞšz`Ì+ê£Ã\$ÑlĞß§ôS¼hÑ­zòº9©ââ AtŸT¢õZj±—uukX§HY^R‹ik]Uy_*ö…F-í‚kZÙ5Â‚'Œh	@t&‰¡Ğ¦)C È\r£h\\-7ˆò.­tiÊ†…¿P­S6×	¡j\rƒ ä’\$Ô*±BÒK/\0)p@!ŠbŒÔ4…¥]1.É:Æ–µÖ»Hı«òBNâ*µÂHŠ|å3³o|Bë[FÏW¤Ò\"ˆ´Öß÷½Ì>¶:ş§R#?!Ë\\åXGÈ”›Åö²é¸É¥n9‘Ä`›ÖZ•ÿ’*KZ‰‡/Ğ”¥.‰m>©cÊš0c Ê9†è9ãxå!Œ£Àà4C-Öƒ@4p£0z\r è8Ax^;ópÂ2\r£Hİ»…ÛğÎŒ£wH<nÃpæ4ı0Dƒä3Œ£§/ŒC`Â7\ra|\$£€Ø2½(èã|àaŠ:\r|†:\rşpÂ5Œ¡\0Ò:o;¸ÛİøË[±±³kn¶·ÓÂ‰¦cëókŒè+ìÚ&ä(@(	‚™ó×JäÕ	«n7g ŸDÂÏ_3ŒÚ«2	SÛKS@ƒ„ˆxÉÃû/åHh•HıUáT2„›šSÒBD))0×œ8˜Ø\\&;oÜ'…0¨Ğ_A3UÌİ­V\0D ™)l‰ş\$Òfˆ*ÿ90­#ò¢Éiû7l`§Ñ¢Ê+=‡©õ‘Ào©\r0§%æ ²Ã¨ nìW„ÜØ qAˆ4†p@ÂˆL˜4¼0@ƒKŠÁQı;´†^~{n=‡ ŞIwÎ„Dboàq*Åèe\$©¼’èø³D‚ â”–KrĞÕ Ñ³I¥­} rlxFƒã)ä&6ã{£C‹2Lçªi\rÚ<×©C€d-ÙØ€Ó3ƒ¹¥¬“!ƒĞ…ÉÃ%o”TDnTœ­;„Y1¡BJ˜%œs•NsÑ*ÑëVÄÈ–¶´H¾äâ3\n!£ö¿&%)Ş0ÄÁ\nÊ¥™AMX¤U¦ÊUZj» ÅbÉ“›Bçt[a\rƒÀVY1…S äEÚ<¦S1•€ ^Èm­Ô'²ç\0U\nƒ€@éCÒsÁœ¡*ˆ¡,')cmDf‡ËÉøÔh¥Qb:KÓH…ê„)95lÀ6bj|\ns=+ÔÉA”ˆ`iâ2„ ¤T’YOF%ÁN€¬)3äH½©{KCF©ˆb-è¥1“TÌŒØ”z]m^ŠF€éJêÃ6Q	3¡\"¶ÈZùˆEÄ;C	ËÊ£‡õ Ôé{WjºNµÕ] Äøj³ä=í&ÃË\r,=\n\ršAn¥Û…ÇT(M‘ƒ €l5–D5Bƒ¿œ>Wa`'AmÊ«Çr1Ğ";break;case"ta":$i="àW* øiÀ¯FÁ\\Hd_†«•Ğô+ÁBQpÌÌ 9‚¢Ğt\\U„«¤êô@‚W¡à(<É\\±”@1	| @(:œ\r†ó	S.WA•èhtå]†R&Êùœñ\\µÌéÓI`ºD®JÉ\$Ôé:º®TÏ X’³`«*ªÉúrj1k€,êÕ…z@%9«Ò5|–Udƒß jä¦¸ˆÁÕàôÉ¾&{,Ÿ™M§¡äS_¶RjØİéÓ^êÊ8<·ZÔ+±õáe~`Š€- uôLš­TÂÈìÕõ&ş÷‰¤R²œ	MºûHI@ˆbÍÒ·õ¬öœÆ2x:MÇ3I¼İG€oe[û‚ßaØÅá\\´JQ‘øa¥r™^)\\õjrôù•ÎqÈ®P\" ˆ­%r*W@h‹¦„)ª¬ø²­\0¡\nù€5Œ6”8‰ªÚ©r¬œ61aË‘ªB˜ºJ²`F«ë´XFÉğP)ƒÒ7ìúÆ– J¬é¸hfÊ4éJøÜĞšRøGªæì¸îºÑÇÂ8Ê7£,‚Ï+ğJ#(´Ë|ØK0**>{Ø¬°|*Äô«A³Ô—\n£˜Ê9HË„O¸ëê\nºÎs‚¿ÎÍÒYI­ñä	¹²\\{CJŒœúÒĞÌ¿/“´o4¾«t½©T';?sôÖİ„£\"•ÔÒˆ\\×mlÚØ´ó³äˆÃ‘Àò2¤#pŞ¨^v˜cÆ…u‹ Êœ°Ô\nzŞîUSìO@‘½ƒ/I…ú¿GqìğÄÇ,š0C±=³+>}¥©·Ëí/;‰L•Ë­ñ8´7\$ş>°l­Vò¥f¼ÜÏë	;9Îmè×•Ñ\rMZÎö4Ga¯®Hş£uÒ{‚)Ø:#„¶1Ò\"Æ©M;¿‹Œ·b*Ğ0]_åØ·˜ªÙ¢S›N\rµÙÕh².:¥§Q&dÈ¦Ù]\"WRØåè\$	Ğš&‡B˜¦ƒ ^6¡x¶<îƒÈ»wŞĞ„İ2«ë‰µP#¤9ÉµøÉ_ÊNòæCdƒwªÅ¢­o>1DCÔ;>!ŠbŒ¤4ì4±§I:ƒ•¦¶H¥õ×rÜ½x\"8íô×>Åyk­Ô¼-V¬ˆÕ1ƒ\"¾2‘bRœKÖ<ktõW)~²“ÚÎà&ŞG§æª’÷Œ1,NWô Ê8·PêÊœ/oê³Âæ»ñ	´B”Ø¾ë€|÷\\)¥Âì:¯y«€_¡RŞ›ÓA{¨¤ƒ»vBîYÛ» *‘a*w€îº9Pp	p³(—“SË\".Q+²tbÈi°2\0(&†æxC ¸9‡pŞ•ĞeÀ4‡ Êp „ˆ|è\"\rĞ:\0tÁxwŠ@¸¢†Ô†¡Át7à¼ñEÃÀx!æàˆ@Âƒ:f‰!|1ÀÂƒX\"Á\$Í†Ã4xƒ <á„ğæ(t\r½]@ß k €4‡Hd¡Ãlo\rL“\n!Ñá<l€è»ÒzK¨p`((€ R\"è‚î\$í‡^}^SzB€ ÀJVÍi‘jD¾<—Ø[?~ÀĞªâÏ,a\nÚaJˆ÷x‘Ù2zN•ı¡ølMd¾r¦`ËÀâw0úÃ˜h¡8CŒùÜKß9®?\"”VS‘r0¬ÚLSßæ<¿•k”„&òf\$˜ê}Áà Â˜TœÈXÀS?hY¡«áÚ'W¸’&l%òµEyŞKM]×¯&FbÀ(g˜3ÎCa‡”7\0ÉC(c¦nfPOôöÉº£…x,†ğêo¡”PA.L(„ÀAKc° Á¦\"`©)ãzº\r&nÈéUÃ’d\$b,Ã°ÎÚôÊ,ÈÊ˜J4DfWh‘îÖäù\\'\rr€³AÕ°òÇ6«½†=uêq»úzk{S}Ô’‚€’xCl )NoÏ«2`CY±ÅôÈœäèÇÊ¼ø_îÒ[›fª-G\r…Ì‚’[Ñ¦?Èj–%2‘r4ÛĞÃC+Şo“ıw/òmsÒ™…”³Íú\$ú-3³ôÚ]˜ù§Ú0~÷<·»%Ï8İÔš”tĞ¿Täl_-£vŒjf¶+ájœtwÕ–«#vì¤\\5ëKÊô©”ähMÿWû\$Ïl£¤–TvŸQw™ş)×Å\$‘¼ÒlaŒ6ÆÁæ©õh0†Àëpä8m¤3™\nğF\r5d6‡X^‹H`€*…@ŒAÀ <T®›¤B¼°)-å*Ö§×6ÕT½²±Ğ8V[4âÊro™ö-[×»FğfQ•ÅŸ*a%’T²µ³°G-ü¹—N`69Š¼æWé‡HdºœF¥T¸*p.ë|pê§<¿‰× c;.Š½.–xSCÄBØ´íù`Q]	.Óµš\$G\n–‚!_´óÁQM>Y… Ë©™æj\$›`›Î#'u|„!˜MÆOç¯ÑlÆs„×¤ëÏ^»CÕ2N¢=öyÄT4Ó•ìKµN2ÄÓªbpÂaDÉ¡œnŒ¡õ6¦A†şæ¤ø—²¦iµ(ï)ÁYb²M´‚:æÁã.´™gU*MÀ8',Ênª\$Gáj/–uÕÍ”59eŒ\rc«–¬È:(œ9ª÷sƒw–…À‘DSRó8_DÙmò6±É]Â—İË¶ÕæåÌÎ•5æ9Ê˜àQÍˆÅXØ";break;case"tr":$i="E6šMÂ	Îi=ÁBQpÌÌ 9‚ˆ†ó™äÂ 3°ÖÆã!”äi6`'“yÈ\\\nb,P!Ú= 2ÀÌ‘H°€Äo<N‡XƒbnŸ§Â)Ì…'‰ÅbæÓ)ØÇ:GX‰ùnÅO‚¤¦“TÂl&#a¼A\$5ÉÄ)\0(–u6&èYÌ@u=\\Î“ë•\n~d¹Í1óq¤@k¸\\¨úDÒ/y:L`”ÚyÒOo¸ÜçÆ:Ñ†¼9Hcà¢™„ó|0œ¬:“I¢Ze^M·;aèÎe”,\rrH(ƒSÌ¦úaÓFL4œò:(–|õ1M&=îc›ŸŠ“}Ëƒƒ4ÖD)Ğa½¬t&QgË‡ÓO³\$ÛLt‰~¦/¸*š2¾o;NõA l¤·Ã“Ø4\r&N10k:<´Š\n¾öióÕ£ÊB6´ƒ,\$¡hàŠ`#ˆòb·\rãpë\ripæEèÚ:Õ?A\0ËEˆº3%ã`È4…ÏËê0££¤…\"9R4a¤ZN¬\r#l–Ò\naĞˆ\"Cš\"-3Hò.…£ÂŒã8aéğÖŸ<B²^ˆDÅ868Í ë)îú ÃĞ@b˜¤#!\0Õ\0F£¨\\É(Ú2#\\9,îêä“ˆC\n°Ô§Ñ/	NÓÁ>³Œ#‚ø2'É8C¨Ú¨Œ®PÊ©°Ğv»­˜ØŸVUm\\WHƒ>9W¬2g%¡`@63ã ÌÉS´ú0ğ+…dÆPĞ•„0òèØ4­ Pš0×Õ\0áxcºJúŒ£ÀàÁŒ£%.\"ãE 3¡Ğ:ƒ€t…ã¾JÜÉ(Î¦8ÀğÈÈ4j„AZ96ã¦\n/¬‹˜ÖÂHÚ8Diˆèã|¢.Î(Ğ7¾£¢ÌÙm€Ó)óg‰Ì°*¯:J†´\"Uí:é\0 \$\ns™&\":…\n-#BP¢>É©Ê‚¤ª\r‰:R• ir?ªHHÌ0KA\r c›âXÃ¥ö¬Vu¨@&[6Ú¢×Šš<Ñ ûØäô¯ÃrO:\rãZlÏ,ÿ4(	â˜¨×£è5­¹\r.–ö¼Ğõ`¨¢ücLÔ¬ñ‚<Û Ì*î<Œô@îµ#4‹\\lU“Ê¤âÈŞlë˜\rò†öÁá\0¦(‰\0Ì˜%£J.#\nšÊæúË±_&h~ĞäâOƒ–äËïò’\ru£’bİø£øÜ1Œ«Šb–Ÿº02µ·êRqĞ7-ü‡’òÁç%À€7`àGÃ±0wˆ³<ó\0dY.È´²WàRŒJ&¼¨ÀÜÌCÉ°#¡Ø0«´\n,hÕ,5‚ÖÍ!mH!­ü,\"aÒs{…q\$ ènÚ¤\0HUµ§¶ÖxDøt0e1Í!NK I„6ãÀVzQËrÀ(#=Å6r‰³B aT*`Z¨cg„3’x†ş\"ò{xLÂ‹šR„9‰‡Éô.fÏ Õ@±IJÔÕ–BÌÓé0)‰|2BätŞá¹]eb(æ¢ã›<W+h9EÉÉ”\$FÀş3z\\	‹0æ\$Í.CJ[¸\n‡eĞ,¹æ¨êI)'í7†ôê¨¼ˆşD™”¤X±>fx^+¹†Q8K\ráÀ‹àˆ«¤ˆ !­‘›™a§\\í,RX¾˜Ha•,°ùÒÂ³Ìû*’¬Á€";break;case"uk":$i="ĞI4‚É ¿h-`­ì&ÑKÁBQpÌÌ 9‚š	Ørñ ¾h-š¸-}[´¹Zõ¢‚•H`Rø¢„˜®dbèÒrbºh d±éZí¢Œ†Gà‹Hü¢ƒ Í\rõMs6@Se+ÈƒE6œJçTd€Jsh\$g\$æG†­fÉj> ”µÂÕêlŠ]H_F¯M<ªhº¦ÁªÑ¨ä*‰6˜JÖ29š<Oq2¨Òy ±¾,*Q¤= ´£Á\$š*!`,‚bš‹İeqQ˜HZeÌÒåM¦\\eŠÓE3¬Â¯öc®Ûb·×hRë½­E%„@öqûæİ/ÓA´Hx„4§™Ğµq¤¦#s›au‘¥Æ™ˆ\\{ ¾YÖÓöK3Eªø…\$E‚4I¡É=JòºG£E\nô»oÉ ú¡JbV 7*ò4M;³pğ°Ä:4O°ÊÊ@£ˆÑFÿš\rêaE	Yxš\$zhÑFi²…;ë¡Æ3j²P˜»*\n&2 ŒšÄ—!,˜Ï§1Ü£)ÉÑ‹ÀK<ÑÛf%Ğ\0H…Á g2†1;¸D4ÅÒ4Ğm:´èL#¼‹ÄÑšó'¦NR¤ *›@¬Çˆ\"…£hÂ4”CÊZ#DºbY©©#t¦ÍÈp˜±SãÚCL=¾Î“)FÂÍ,ãO.jèõ&(jO) ‹Ô`¦AL¥—áp´´Õ¢’•µ½^¤×r|İË–©a)6%dVjU—9YÍ] ³ZLM.¦£É#”Ë£IKEF¼haÑ RŞ÷´\$P£JahZåà\\-ÁÄ\n.Ñ	,eUÑ,ûB©)uÊ \rƒ å;ÀJÊ\rŒÈËøº¡²0†)ŠB3!P!Åè\\‚#–bºã¨Ë¤F,\n¨§¦ÄJâ%eº×D¶3ª‹o6Mî`šŞ1óÒßZåÂ×	H\né/^È¶Ê!&©ø„ê%(4W”¢z¨®„|\r¼©s\n©µj‰ØaÓ‹QšUë«\\Ø</åÀğn…–”ïiŠâËxÚÙµ±ªuàÙEˆy-ˆ¥º»öB¸\nzx&Œ#˜è2A\0áÌc¸Ş9Iƒ(ğ8\r#Ê2eaàÂ\rPÌ„C@è:Ğ^ıè\\0ŒƒhÒ7swF3…ã(İäØÜ9#– Â9ã(éÚãØ0ÃXD	#hà6£o”:xÂ9Ô:\r|˜:\rÿÂ5Œ¡\0ÒóœRO7GGIQÄk‡ä6Z4â¨)&ü[‚\0 €-/,ã›s’CÙ (,à¥¼3¢NS–©à6ÌÜ¨æøTD{p8Mm§\\KZ§†ANDvA\n!³.¥N ¸Ò,!@K6%\"6è°!ªd#DBµDVyE“+[‰Éº6ÓZJ›)4,£œbºÌÎakSDµV›–‚B‰!S?Ä‚bh¼‚Á<)…Dè†ŠI·±iM(\\›‘©)±ÜhÇ’d–ôb<¨²3H–èBy1Ap©ˆÁâ‡	)Š~'lâpTÅ›YQƒDËA.OÈo €1¾\0@_8ox\0×† ÒÁ\0S\n!0e(ÿC°iuá*	x’`i|Î\0?ùŠƒxm#p;<U2’KX[°œÙHÔ´#Tà;ÓˆÊBr¶ÚYI\$­\$ˆÏ;Q»;æD…ÎutZĞÃB‡ˆ‘X1Õ¯pN±(‚Ë	dìİ¹ü‡\0äfKè{!Íê¿™p›z¡ 4ÑÇ¬÷ƒ,é2ld‡J”g7éZ hõÉø6ÖÈY\"6ôÒÈ	R•Êƒ¦\rÒâjä§Ò^T¨õÂ+§ÂJœæ&t¾-¬©Ş—ŠãWz•¶¬§jG+•bx­Î„Îñ«ë&H³zÈ «5\\­\r’†€ †Åƒ`+;lÄí¦ø­bEMD³Å0³™X§…;iè)Á|O4Ç~AÕÍ\0Äÿ^ \n¡P#ĞpPc~Ï3“ÅyBiÀºŸ(ò‘ãÁÉ,	\\¬#šÒ…aµ¨LÚ“E?,}´µŞ,Z²Oní}¾¶Sşƒâ@H£‰&h*)‡ÓIødãŒ¬)™SêŸÓJ“†’ä†4Ğ²G(”ŸŠ¡)ê£7FğßEh†³ï*9°ÑÚÄVK@åc;°GÂ#–Ÿoud5Äh½Ïº‚±\0PL—A’Òa&üı¤Y%ÛZµ…ÕÂ`Ø}\nâ+mqà²#Lm·B-ÖLe2\$½ÎÒìKà•ˆE\näˆ\"4!–¾=»õPT&ÓÜÒ\$… ¬H]“,„):ù4Ê£“é‰â)›õ@±¤AÀ";break;case"zh":$i="ä^¨ês•\\šr¤îõâ|%ÌÂ:\$\nr.®„ö2Šr/d²È»[8Ğ S™8€r©!T¡\\¸s¦’I4¢b§r¬ñ•Ğ€Js!Kd²u´eåV¦©ÅDªXçT®NTr}Ê§EËVJr%Ğ¡ªÊÁBÀS¡^­t*…êıÎ”T[UëxÚğè_¦\\‹¤Û™©r¬R±•lå	@FUPÄÕ­J­œ«u•B¥TËİÕdBİÎ±]¹SÖ2UaPKËRêYr}Ì—[:RëJÚµ.çV)£+(Âé€M¹Q`Sz‘s®Ó•´:‚\0•r¦×ÎUêŠ¶ˆKÙï.uâYĞ¾H¹S>;Æ‘o	FÖÕèg:ÍLêW©XÂı:s„	Îü»âH×—eÑÒP—(xÈ6A–#(æ\rÃx!pHÃÁªZH‡9hï\$åÙÌB(eéçÉiI¥Ñ\$É ƒJÙT ™Ò@'1T¹¤¡j¨IW)‰i:R9TŒÙÒQ	i¥,pL±éB¶G’2îÁê	'J	ó8rÍSBG5ê \$	Ğš&‡B˜¦cÍ<‹¡pÚ6…Ã È––eAÒJ.r‹#µ¥ÌFÆ#`è9%¤âE/RòÛ¤d©b˜¤#	9Hs‘\$b¬täl:M\$j´¤i*ËœÔ&@4‘ÙO²é	4·§)~BÁeÙĞQhI^ÑY¥åWÚ,ã\0C¶¨c½×Ñ,X@oÕ†µU\riråm6W2âhÂ9ƒ(äØæ;ã”2€Ò9£ \\ƒ@4aƒ0z\r è8Ax^;äpÂ2\r£HİØ(ÎŒ£vX<_£pæ4ùpDƒä3Œ£¦./ŒC`Â7\ra|\$£€Ø2¹hèã|£aš:\r|:\rú°Â5Œ¡\0Ò:`ğÛ¡éÁrÄ²’+ÄÀËÊs…|'A\0 \$\nÄ1S¥¶¼!„B†,¹Ğ[·\\˜&G*h›'	Ó`sÅ­3\\ê²Ü÷”DñÊE£.˜•Iû½	â˜©¿5×º­zê	#LŞí#Ì@§/CÑôª8@t±yz‚’(ñvR2âÈŞ:„†ºPß‘„Ä4Œá\0¦(‰\0Ì4éa\0ì4âB0©¾èpxÓ¤à»&ÇñCxÚwğí”+s™(È¤Abäs‰µğZŒØ²ÂlZ x`9,#™/¹!Kà1–-Gó†ÖYK:¶-O9è€ÂşÁ`È4¾v–Ï«\\zÀ2úÎ@i†!… †VÔ9ñ?«D¨Qr-ˆ§:&4òq#ÌôFĞQ0\nR2\"Ì’;ğ\0LŠD¬EÀªƒG‰s”kÅè±¢LF™p†§Ã`+a°4†0Ö«Ù|Á„6VÃƒkZ\r!˜<¶ èÚ‚3äl!´:¯À@›&T*`Z-r7vÔ›!#”W‹È,³HÑªY#\n’Ü•AÍ‰°y”ôlVÄ(³JDÈ Eyt/Â¢DáÒ\$ÅğæÆÑ)Ä\\İÌ¯¢\0^t-GH;Î<„ˆà¸ddÁoÎ¬Á[5g1~’Ñ²iÑc˜O7\$\"ÇHšÇÊSrµ)%2a+ePñÁÌ‰0¢w\np¶ËB¦e”=\"I1P";break;case"zh-tw":$i="ä^¨ê%Ó•\\šr¥ÑÎõâ|%ÌÎu:HçB(\\Ë4«‘pŠr –neRQÌ¡D8Ğ S•\nt*.tÒI&”G‘N”ÊAÊ¤S¹V÷:	t%9Sy:\"<r«STâ¢.©‚ ’Ôr}Ê§EÒÖI'2qèY¡ÜÉdË¡B¨•K€§B©=1@ ÷:R¬èU¢ïwÕDyåD%åËhò¶<€r b)àèe7Í&óp‚‘q¥Éi®UºÊ£Sªè0wçB\néP§œ©ë™*¸¨¥éiu-•>æL )dœµZ—s«Ñå•étŒt 4È…´]l²t-ÕòÕÊú\0•–âmÕg:İPè^+Ü©š³•p³t&aÎUz[s¦Wrå--`\\…É\nü¿d+úµ–)v]%\n‡Œ\0Ä<²á@æCÈÊ9CpŞˆ\\qj¥’â„°œ…Ùvs„z\0Fœåé\\¥‘Ä‘Ê]àRxŸ éi—¤ä|@—1&CÇÄÛöV’IZÅ®©È_ÇAU/EÁvØŠY@Ññ2BJ¨¹2^:\$\nZÒ¿3a\0@³ĞŠ„@ÊR¡v[49_*Š°\$Bhš\nb˜-4hò.…ÃhÚƒ\$ÔıåÔ\$ÉkBÔ¶C`è9/Eát²ªb.Œ¶ÄBhB¦)Á\0è7lÂâÀgIFÁŒÚñœ¤±]>¿G-2s	ñO´œ6Í3Ê_µåtEYÊJ“ì%£i³lëHY\$	ß!„ey_X±a?“íXJÕÉ©ÌC'AZD´hÂ9²ã@8`c˜î7P‹*8\r#Ê2Á\0x0„F\$3¡Ğ:ƒ€t…ã¾L# Ú4Ã(äaƒ8^Ìfl³1j\rÁxE[#Î2˜è¾1\rƒÜ5„Ağ’6`Ê6³ xŒ!ó<9„€è4\rğo¬Œ5È@4˜>\\6èÚˆ\\±,‹0A%‰q\ns	ÎZ›bÊDÅ¥Û¾s…yÌG–•x \$\n†åºnÛÂD)f\0•®…³_u,¬H„%éŠfš·'1\\Z¶ÅÅ|G×l	ÊQ6DQ@Ïò‚€)Š<µ“)ùTŸ”ë‰Ì\\jú´÷a,Ö½_ZPjá^˜•¨2­4ÈŞ:„Œºhß”„ÀÄ4Œá\0¦(‰\0Ì4éÁ\0ì4ã0¨(|#t\"4é˜fË²}CŞA\0seÁÙ—6±Ğ*WBâlW.u¸·„@ª'‚‘7‹!Ì&Å¢A¨ÎàÓ ¥ƒp,W;XyO9éoE˜·ÇªõØÈa€€0pä_{Ngía¯½ĞÈLº¶\r¦ˆ[Xå†˜©¡~'M9Ãœ\\‹ÁÎsáAè;q*&	ˆ¥á†1æD°˜ˆ\$&` %‘’ÀÁÊ#ÌPˆA\"ôX‘>/Í\0CTa°†0ØCkUŒ`Â¨eVÁ½¬µĞÒƒËc¬#>ÉC«BR)–\0ª0-P79ƒ;kPµs”!½\\¡#Dqt©ÁÎÃÄ-‡Œ‹h´™Š€†Kƒ¤K‰ÁĞ NÙ«dôFİÄ‚WÃ_Šç\0.M\rMÎdr‹ácæĞ\n	|22ÀÏ8§\$b@‚‡@Â„!iK\"½ÍÌ'’0è’ÜQ‹ÒZ#R™Ÿç©2¨n[Ø«È¥—It[Kyt\"1ÒËØSABR\0";break;}$xe=array();foreach(explode("\n",lzw_decompress($i))as$X)$xe[]=(strpos($X,"\t")?explode("\t",$X):$X);return$xe;}if(!$xe)$xe=get_translations($a);if(extension_loaded('pdo')){class
Min_PDO
extends
PDO{var$_result,$server_info,$affected_rows,$errno,$error;function
__construct(){global$c;$sd=array_search("SQL",$c->operators);if($sd!==false)unset($c->operators[$sd]);}function
dsn($ib,$V,$F,$zb='auth_error'){set_exception_handler($zb);parent::__construct($ib,$V,$F);restore_exception_handler();$this->setAttribute(13,array('Min_PDOStatement'));$this->server_info=$this->getAttribute(4);}function
query($H,$Ce=false){$I=parent::query($H);$this->error="";if(!$I){list(,$this->errno,$this->error)=$this->errorInfo();return
false;}$this->store_result($I);return$I;}function
multi_query($H){return$this->_result=$this->query($H);}function
store_result($I=null){if(!$I){$I=$this->_result;if(!$I)return
false;}if($I->columnCount()){$I->num_rows=$I->rowCount();return$I;}$this->affected_rows=$I->rowCount();return
true;}function
next_result(){if(!$this->_result)return
false;$this->_result->_offset=0;return@$this->_result->nextRowset();}function
result($H,$p=0){$I=$this->query($H);if(!$I)return
false;$K=$I->fetch();return$K[$p];}}class
Min_PDOStatement
extends
PDOStatement{var$_offset=0,$num_rows;function
fetch_assoc(){return$this->fetch(2);}function
fetch_row(){return$this->fetch(3);}function
fetch_field(){$K=(object)$this->getColumnMeta($this->_offset++);$K->orgtable=$K->table;$K->orgname=$K->name;$K->charsetnr=(in_array("blob",(array)$K->flags)?63:0);return$K;}}}$gb=array();$gb["sqlite"]="SQLite 3";$gb["sqlite2"]="SQLite 2";if(isset($_GET["sqlite"])||isset($_GET["sqlite2"])){$td=array((isset($_GET["sqlite"])?"SQLite3":"SQLite"),"PDO_SQLite");define("DRIVER",(isset($_GET["sqlite"])?"sqlite":"sqlite2"));if(class_exists(isset($_GET["sqlite"])?"SQLite3":"SQLiteDatabase")){if(isset($_GET["sqlite"])){class
Min_SQLite{var$extension="SQLite3",$server_info,$affected_rows,$errno,$error,$_link;function
Min_SQLite($Hb){$this->_link=new
SQLite3($Hb);$Oe=$this->_link->version();$this->server_info=$Oe["versionString"];}function
query($H){$I=@$this->_link->query($H);$this->error="";if(!$I){$this->errno=$this->_link->lastErrorCode();$this->error=$this->_link->lastErrorMsg();return
false;}elseif($I->numColumns())return
new
Min_Result($I);$this->affected_rows=$this->_link->changes();return
true;}function
quote($P){return(is_utf8($P)?"'".$this->_link->escapeString($P)."'":"x'".reset(unpack('H*',$P))."'");}function
store_result(){return$this->_result;}function
result($H,$p=0){$I=$this->query($H);if(!is_object($I))return
false;$K=$I->_result->fetchArray();return$K[$p];}}class
Min_Result{var$_result,$_offset=0,$num_rows;function
Min_Result($I){$this->_result=$I;}function
fetch_assoc(){return$this->_result->fetchArray(SQLITE3_ASSOC);}function
fetch_row(){return$this->_result->fetchArray(SQLITE3_NUM);}function
fetch_field(){$g=$this->_offset++;$U=$this->_result->columnType($g);return(object)array("name"=>$this->_result->columnName($g),"type"=>$U,"charsetnr"=>($U==SQLITE3_BLOB?63:0),);}function
__desctruct(){return$this->_result->finalize();}}}else{class
Min_SQLite{var$extension="SQLite",$server_info,$affected_rows,$error,$_link;function
Min_SQLite($Hb){$this->server_info=sqlite_libversion();$this->_link=new
SQLiteDatabase($Hb);}function
query($H,$Ce=false){$Rc=($Ce?"unbufferedQuery":"query");$I=@$this->_link->$Rc($H,SQLITE_BOTH,$o);$this->error="";if(!$I){$this->error=$o;return
false;}elseif($I===true){$this->affected_rows=$this->changes();return
true;}return
new
Min_Result($I);}function
quote($P){return"'".sqlite_escape_string($P)."'";}function
store_result(){return$this->_result;}function
result($H,$p=0){$I=$this->query($H);if(!is_object($I))return
false;$K=$I->_result->fetch();return$K[$p];}}class
Min_Result{var$_result,$_offset=0,$num_rows;function
Min_Result($I){$this->_result=$I;if(method_exists($I,'numRows'))$this->num_rows=$I->numRows();}function
fetch_assoc(){$K=$this->_result->fetch(SQLITE_ASSOC);if(!$K)return
false;$J=array();foreach($K
as$y=>$X)$J[($y[0]=='"'?idf_unescape($y):$y)]=$X;return$J;}function
fetch_row(){return$this->_result->fetch(SQLITE_NUM);}function
fetch_field(){$C=$this->_result->fieldName($this->_offset++);$G='(\\[.*]|"(?:[^"]|"")*"|(.+))';if(preg_match("~^($G\\.)?$G\$~",$C,$A)){$Q=($A[3]!=""?$A[3]:idf_unescape($A[2]));$C=($A[5]!=""?$A[5]:idf_unescape($A[4]));}return(object)array("name"=>$C,"orgname"=>$C,"orgtable"=>$Q,);}}}}elseif(extension_loaded("pdo_sqlite")){class
Min_SQLite
extends
Min_PDO{var$extension="PDO_SQLite";function
Min_SQLite($Hb){$this->dsn(DRIVER.":$Hb","","");}}}if(class_exists("Min_SQLite")){class
Min_DB
extends
Min_SQLite{function
Min_DB(){$this->Min_SQLite(":memory:");}function
select_db($Hb){if(is_readable($Hb)&&$this->query("ATTACH ".$this->quote(ereg("(^[/\\\\]|:)",$Hb)?$Hb:dirname($_SERVER["SCRIPT_FILENAME"])."/$Hb")." AS a")){$this->Min_SQLite($Hb);return
true;}return
false;}function
multi_query($H){return$this->_result=$this->query($H);}function
next_result(){return
false;}}}function
idf_escape($u){return'"'.str_replace('"','""',$u).'"';}function
table($u){return
idf_escape($u);}function
connect(){return
new
Min_DB;}function
get_databases(){return
array();}function
limit($H,$Z,$z,$Xc=0,$Sd=" "){return" $H$Z".($z!==null?$Sd."LIMIT $z".($Xc?" OFFSET $Xc":""):"");}function
limit1($H,$Z){global$j;return($j->result("SELECT sqlite_compileoption_used('ENABLE_UPDATE_DELETE_LIMIT')")?limit($H,$Z,1):" $H$Z");}function
db_collation($n,$La){global$j;return$j->result("PRAGMA encoding");}function
engines(){return
array();}function
logged_user(){return
get_current_user();}function
tables_list(){return
get_key_vals("SELECT name, type FROM sqlite_master WHERE type IN ('table', 'view') ORDER BY (name = 'sqlite_sequence'), name",1);}function
count_tables($m){return
array();}function
table_status($C=""){global$j;$J=array();foreach(get_rows("SELECT name AS Name, type AS Engine FROM sqlite_master WHERE type IN ('table', 'view') ".($C!=""?"AND name = ".q($C):"ORDER BY name"))as$K){$K["Oid"]=1;$K["Auto_increment"]="";$K["Rows"]=$j->result("SELECT COUNT(*) FROM ".idf_escape($K["Name"]));$J[$K["Name"]]=$K;}foreach(get_rows("SELECT * FROM sqlite_sequence",null,"")as$K)$J[$K["name"]]["Auto_increment"]=$K["seq"];return($C!=""?$J[$C]:$J);}function
is_view($R){return$R["Engine"]=="view";}function
fk_support($R){global$j;return!$j->result("SELECT sqlite_compileoption_used('OMIT_FOREIGN_KEY')");}function
fields($Q){$J=array();foreach(get_rows("PRAGMA table_info(".table($Q).")")as$K){$U=strtolower($K["type"]);$Ya=$K["dflt_value"];$J[$K["name"]]=array("field"=>$K["name"],"type"=>(eregi("int",$U)?"integer":(eregi("char|clob|text",$U)?"text":(eregi("blob",$U)?"blob":(eregi("real|floa|doub",$U)?"real":"numeric")))),"full_type"=>$U,"default"=>(ereg("'(.*)'",$Ya,$A)?str_replace("''","'",$A[1]):($Ya=="NULL"?null:$Ya)),"null"=>!$K["notnull"],"auto_increment"=>eregi('^integer$',$U)&&$K["pk"],"privileges"=>array("select"=>1,"insert"=>1,"update"=>1),"primary"=>$K["pk"],);}return$J;}function
indexes($Q,$k=null){$J=array();$ud=array();foreach(fields($Q)as$p){if($p["primary"])$ud[]=$p["field"];}if($ud)$J[""]=array("type"=>"PRIMARY","columns"=>$ud,"lengths"=>array());$Xd=get_key_vals("SELECT name, sql FROM sqlite_master WHERE type = 'index' AND tbl_name = ".q($Q));foreach(get_rows("PRAGMA index_list(".table($Q).")")as$K){$C=$K["name"];if(!ereg("^sqlite_",$C)){$J[$C]["type"]=($K["unique"]?"UNIQUE":"INDEX");$J[$C]["lengths"]=array();foreach(get_rows("PRAGMA index_info(".idf_escape($C).")")as$Ld)$J[$C]["columns"][]=$Ld["name"];$J[$C]["descs"]=array();if(eregi('^CREATE( UNIQUE)? INDEX '.quotemeta(idf_escape($C).' ON '.idf_escape($Q)).' \((.*)\)$',$Xd[$C],$Dd)){preg_match_all('/("[^"]*+")+( DESC)?/',$Dd[2],$Jc);foreach($Jc[2]as$X)$J[$C]["descs"][]=($X?'1':null);}}}return$J;}function
foreign_keys($Q){$J=array();foreach(get_rows("PRAGMA foreign_key_list(".table($Q).")")as$K){$r=&$J[$K["id"]];if(!$r)$r=$K;$r["source"][]=$K["from"];$r["target"][]=$K["to"];}return$J;}function
view($C){global$j;return
array("select"=>preg_replace('~^(?:[^`"[]+|`[^`]*`|"[^"]*")* AS\\s+~iU','',$j->result("SELECT sql FROM sqlite_master WHERE name = ".q($C))));}function
collations(){return(isset($_GET["create"])?get_vals("PRAGMA collation_list",1):array());}function
information_schema($n){return
false;}function
error(){global$j;return
h($j->error);}function
check_sqlite_name($C){global$j;$Cb="db|sdb|sqlite";if(!preg_match("~^[^\\0]*\\.($Cb)\$~",$C)){$j->error=lang(11,str_replace("|",", ",$Cb));return
false;}return
true;}function
create_database($n,$f){global$j;if(file_exists($n)){$j->error=lang(12);return
false;}if(!check_sqlite_name($n))return
false;$_=new
Min_SQLite($n);$_->query('PRAGMA encoding = "UTF-8"');$_->query('CREATE TABLE adminer (i)');$_->query('DROP TABLE adminer');return
true;}function
drop_databases($m){global$j;$j->Min_SQLite(":memory:");foreach($m
as$n){if(!@unlink($n)){$j->error=lang(12);return
false;}}return
true;}function
rename_database($C,$f){global$j;if(!check_sqlite_name($C))return
false;$j->Min_SQLite(":memory:");$j->error=lang(12);return@rename(DB,$C);}function
auto_increment(){return" PRIMARY KEY".(DRIVER=="sqlite"?" AUTOINCREMENT":"");}function
alter_table($Q,$C,$q,$Mb,$Oa,$sb,$f,$ta,$pd){$Le=($Q==""||$Mb);foreach($q
as$p){if($p[0]!=""||!$p[1]||$p[2]){$Le=true;break;}}$d=array();$kd=array();$vd=false;foreach($q
as$p){if($p[1]){if($p[1][6])$vd=true;$d[]=($Le?"  ":"ADD ").implode($p[1]);if($p[0]!="")$kd[$p[0]]=$p[1][0];}}if($Le){if($Q!=""){queries("BEGIN");foreach(foreign_keys($Q)as$r){$h=array();foreach($r["source"]as$g){if(!$kd[$g])continue
2;$h[]=$kd[$g];}$Mb[]="  FOREIGN KEY (".implode(", ",$h).") REFERENCES ".table($r["table"])." (".implode(", ",array_map('idf_escape',$r["target"])).") ON DELETE $r[on_delete] ON UPDATE $r[on_update]";}$w=array();foreach(indexes($Q)as$vc=>$v){$h=array();foreach($v["columns"]as$g){if(!$kd[$g])continue
2;$h[]=$kd[$g];}$h="(".implode(", ",$h).")";if($v["type"]!="PRIMARY")$w[]=array($v["type"],$vc,$h);elseif(!$vd)$Mb[]="  PRIMARY KEY $h";}}$d=array_merge($d,$Mb);if(!queries("CREATE TABLE ".table($Q!=""?"adminer_$C":$C)." (\n".implode(",\n",$d)."\n)"))return
false;if($Q!=""){if($kd&&!queries("INSERT INTO ".table("adminer_$C")." (".implode(", ",$kd).") SELECT ".implode(", ",array_map('idf_escape',array_keys($kd)))." FROM ".table($Q)))return
false;$_e=array();foreach(triggers($Q)as$ze=>$oe){$ye=trigger($ze);$_e[]="CREATE TRIGGER ".idf_escape($ze)." ".implode(" ",$oe)." ON ".table($C)."\n$ye[Statement]";}if(!queries("DROP TABLE ".table($Q)))return
false;queries("ALTER TABLE ".table("adminer_$C")." RENAME TO ".table($C));if(!alter_indexes($C,$w))return
false;foreach($_e
as$ye){if(!queries($ye))return
false;}queries("COMMIT");}}else{foreach($d
as$X){if(!queries("ALTER TABLE ".table($Q)." $X"))return
false;}if($Q!=$C&&!queries("ALTER TABLE ".table($Q)." RENAME TO ".table($C)))return
false;}if($ta)queries("UPDATE sqlite_sequence SET seq = $ta WHERE name = ".q($C));return
true;}function
index_sql($Q,$U,$C,$h){return"CREATE $U ".($U!="INDEX"?"INDEX ":"").idf_escape($C!=""?$C:uniqid($Q."_"))." ON ".table($Q)." $h";}function
alter_indexes($Q,$d){foreach(array_reverse($d)as$X){if(!queries($X[2]=="DROP"?"DROP INDEX ".idf_escape($X[1]):index_sql($Q,$X[0],$X[1],$X[2])))return
false;}return
true;}function
truncate_tables($S){return
apply_queries("DELETE FROM",$S);}function
drop_views($Qe){return
apply_queries("DROP VIEW",$Qe);}function
drop_tables($S){return
apply_queries("DROP TABLE",$S);}function
move_tables($S,$Qe,$T){return
false;}function
trigger($C){global$j;if($C=="")return
array("Statement"=>"BEGIN\n\t;\nEND");preg_match('~^CREATE\\s+TRIGGER\\s*(?:[^`"\\s]+|`[^`]*`|"[^"]*")+\\s*([a-z]+)\\s+([a-z]+)\\s+ON\\s*(?:[^`"\\s]+|`[^`]*`|"[^"]*")+\\s*(?:FOR\\s*EACH\\s*ROW\\s)?(.*)~is',$j->result("SELECT sql FROM sqlite_master WHERE name = ".q($C)),$A);return
array("Timing"=>strtoupper($A[1]),"Event"=>strtoupper($A[2]),"Trigger"=>$C,"Statement"=>$A[3]);}function
triggers($Q){$J=array();foreach(get_rows("SELECT * FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ".q($Q))as$K){preg_match('~^CREATE\\s+TRIGGER\\s*(?:[^`"\\s]+|`[^`]*`|"[^"]*")+\\s*([a-z]+)\\s*([a-z]+)~i',$K["sql"],$A);$J[$K["name"]]=array($A[1],$A[2]);}return$J;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER","INSTEAD OF"),"Type"=>array("FOR EACH ROW"),);}function
routine($C,$U){}function
routines(){}function
routine_languages(){}function
begin(){return
queries("BEGIN");}function
insert_into($Q,$O){return
queries("INSERT INTO ".table($Q).($O?" (".implode(", ",array_keys($O)).")\nVALUES (".implode(", ",$O).")":"DEFAULT VALUES"));}function
insert_update($Q,$O,$ud){return
queries("REPLACE INTO ".table($Q)." (".implode(", ",array_keys($O)).") VALUES (".implode(", ",$O).")");}function
last_id(){global$j;return$j->result("SELECT LAST_INSERT_ROWID()");}function
explain($j,$H){return$j->query("EXPLAIN $H");}function
found_rows($R,$Z){}function
types(){return
array();}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($Od){return
true;}function
create_sql($Q,$ta){global$j;$J=$j->result("SELECT sql FROM sqlite_master WHERE type IN ('table', 'view') AND name = ".q($Q));foreach(indexes($Q)as$C=>$v){if($C=='')continue;$J.=";\n\n".index_sql($Q,$v['type'],$C,"(".implode(", ",array_map('idf_escape',$v['columns'])).")");}return$J;}function
truncate_sql($Q){return"DELETE FROM ".table($Q);}function
use_sql($l){}function
trigger_sql($Q,$ce){return
implode(get_vals("SELECT sql || ';;\n' FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ".q($Q)));}function
show_variables(){global$j;$J=array();foreach(array("auto_vacuum","cache_size","count_changes","default_cache_size","empty_result_callbacks","encoding","foreign_keys","full_column_names","fullfsync","journal_mode","journal_size_limit","legacy_file_format","locking_mode","page_size","max_page_count","read_uncommitted","recursive_triggers","reverse_unordered_selects","secure_delete","short_column_names","synchronous","temp_store","temp_store_directory","schema_version","integrity_check","quick_check")as$y)$J[$y]=$j->result("PRAGMA $y");return$J;}function
show_status(){$J=array();foreach(get_vals("PRAGMA compile_options")as$fd){list($y,$X)=explode("=",$fd,2);$J[$y]=$X;}return$J;}function
convert_field($p){}function
unconvert_field($p,$J){return$J;}function
support($Fb){return
ereg('^(view|trigger|variables|status|dump|move_col|drop_col)$',$Fb);}$x="sqlite";$Be=array("integer"=>0,"real"=>0,"numeric"=>0,"text"=>0,"blob"=>0);$be=array_keys($Be);$Ie=array();$ed=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL","SQL");$Wb=array("hex","length","lower","round","unixepoch","upper");$Zb=array("avg","count","count distinct","group_concat","max","min","sum");$kb=array(array(),array("integer|real|numeric"=>"+/-","text"=>"||",));}$gb["pgsql"]="PostgreSQL";if(isset($_GET["pgsql"])){$td=array("PgSQL","PDO_PgSQL");define("DRIVER","pgsql");if(extension_loaded("pgsql")){class
Min_DB{var$extension="PgSQL",$_link,$_result,$_string,$_database=true,$server_info,$affected_rows,$error;function
_error($vb,$o){if(ini_bool("html_errors"))$o=html_entity_decode(strip_tags($o));$o=ereg_replace('^[^:]*: ','',$o);$this->error=$o;}function
connect($N,$V,$F){global$c;$n=$c->database();set_error_handler(array($this,'_error'));$this->_string="host='".str_replace(":","' port='",addcslashes($N,"'\\"))."' user='".addcslashes($V,"'\\")."' password='".addcslashes($F,"'\\")."'";$this->_link=@pg_connect("$this->_string dbname='".($n!=""?addcslashes($n,"'\\"):"postgres")."'",PGSQL_CONNECT_FORCE_NEW);if(!$this->_link&&$n!=""){$this->_database=false;$this->_link=@pg_connect("$this->_string dbname='postgres'",PGSQL_CONNECT_FORCE_NEW);}restore_error_handler();if($this->_link){$Oe=pg_version($this->_link);$this->server_info=$Oe["server"];pg_set_client_encoding($this->_link,"UTF8");}return(bool)$this->_link;}function
quote($P){return"'".pg_escape_string($this->_link,$P)."'";}function
select_db($l){global$c;if($l==$c->database())return$this->_database;$J=@pg_connect("$this->_string dbname='".addcslashes($l,"'\\")."'",PGSQL_CONNECT_FORCE_NEW);if($J)$this->_link=$J;return$J;}function
close(){$this->_link=@pg_connect("$this->_string dbname='postgres'");}function
query($H,$Ce=false){$I=@pg_query($this->_link,$H);$this->error="";if(!$I){$this->error=pg_last_error($this->_link);return
false;}elseif(!pg_num_fields($I)){$this->affected_rows=pg_affected_rows($I);return
true;}return
new
Min_Result($I);}function
multi_query($H){return$this->_result=$this->query($H);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($H,$p=0){$I=$this->query($H);if(!$I||!$I->num_rows)return
false;return
pg_fetch_result($I->_result,0,$p);}}class
Min_Result{var$_result,$_offset=0,$num_rows;function
Min_Result($I){$this->_result=$I;$this->num_rows=pg_num_rows($I);}function
fetch_assoc(){return
pg_fetch_assoc($this->_result);}function
fetch_row(){return
pg_fetch_row($this->_result);}function
fetch_field(){$g=$this->_offset++;$J=new
stdClass;if(function_exists('pg_field_table'))$J->orgtable=pg_field_table($this->_result,$g);$J->name=pg_field_name($this->_result,$g);$J->orgname=$J->name;$J->type=pg_field_type($this->_result,$g);$J->charsetnr=($J->type=="bytea"?63:0);return$J;}function
__destruct(){pg_free_result($this->_result);}}}elseif(extension_loaded("pdo_pgsql")){class
Min_DB
extends
Min_PDO{var$extension="PDO_PgSQL";function
connect($N,$V,$F){global$c;$n=$c->database();$P="pgsql:host='".str_replace(":","' port='",addcslashes($N,"'\\"))."' options='-c client_encoding=utf8'";$this->dsn("$P dbname='".($n!=""?addcslashes($n,"'\\"):"postgres")."'",$V,$F);return
true;}function
select_db($l){global$c;return($c->database()==$l);}function
close(){}}}function
idf_escape($u){return'"'.str_replace('"','""',$u).'"';}function
table($u){return
idf_escape($u);}function
connect(){global$c;$j=new
Min_DB;$Va=$c->credentials();if($j->connect($Va[0],$Va[1],$Va[2])){if($j->server_info>=9)$j->query("SET application_name = 'Adminer'");return$j;}return$j->error;}function
get_databases(){return
get_vals("SELECT datname FROM pg_database ORDER BY datname");}function
limit($H,$Z,$z,$Xc=0,$Sd=" "){return" $H$Z".($z!==null?$Sd."LIMIT $z".($Xc?" OFFSET $Xc":""):"");}function
limit1($H,$Z){return" $H$Z";}function
db_collation($n,$La){global$j;return$j->result("SHOW LC_COLLATE");}function
engines(){return
array();}function
logged_user(){global$j;return$j->result("SELECT user");}function
tables_list(){return
get_key_vals("SELECT table_name, table_type FROM information_schema.tables WHERE table_schema = current_schema() ORDER BY table_name");}function
count_tables($m){return
array();}function
table_status($C=""){$J=array();foreach(get_rows("SELECT relname AS \"Name\", CASE relkind WHEN 'r' THEN 'table' ELSE 'view' END AS \"Engine\", pg_relation_size(oid) AS \"Data_length\", pg_total_relation_size(oid) - pg_relation_size(oid) AS \"Index_length\", obj_description(oid, 'pg_class') AS \"Comment\", relhasoids::int AS \"Oid\", reltuples as \"Rows\"
FROM pg_class
WHERE relkind IN ('r','v')
AND relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema())
".($C!=""?"AND relname = ".q($C):"ORDER BY relname"))as$K)$J[$K["Name"]]=$K;return($C!=""?$J[$C]:$J);}function
is_view($R){return$R["Engine"]=="view";}function
fk_support($R){return
true;}function
fields($Q){$J=array();$na=array('timestamp without time zone'=>'timestamp','timestamp with time zone'=>'timestamptz',);foreach(get_rows("SELECT a.attname AS field, format_type(a.atttypid, a.atttypmod) AS full_type, d.adsrc AS default, a.attnotnull::int, col_description(c.oid, a.attnum) AS comment
FROM pg_class c
JOIN pg_namespace n ON c.relnamespace = n.oid
JOIN pg_attribute a ON c.oid = a.attrelid
LEFT JOIN pg_attrdef d ON c.oid = d.adrelid AND a.attnum = d.adnum
WHERE c.relname = ".q($Q)."
AND n.nspname = current_schema()
AND NOT a.attisdropped
AND a.attnum > 0
ORDER BY a.attnum")as$K){$U=$K["full_type"];if(ereg('(.+)\\((.*)\\)$',$K["full_type"],$A))list(,$U,$K["length"])=$A;$K["type"]=($na[$U]?$na[$U]:$U);$K["full_type"]=$K["type"].($K["length"]?"($K[length])":"");$K["null"]=!$K["attnotnull"];$K["auto_increment"]=eregi("^nextval\\(",$K["default"]);$K["privileges"]=array("insert"=>1,"select"=>1,"update"=>1);if(preg_match('~^(.*)::.+$~',$K["default"],$A))$K["default"]=($A[1][0]=="'"?idf_unescape($A[1]):$A[1]);$J[$K["field"]]=$K;}return$J;}function
indexes($Q,$k=null){global$j;if(!is_object($k))$k=$j;$J=array();$je=$k->result("SELECT oid FROM pg_class WHERE relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema()) AND relname = ".q($Q));$h=get_key_vals("SELECT attnum, attname FROM pg_attribute WHERE attrelid = $je AND attnum > 0",$k);foreach(get_rows("SELECT relname, indisunique::int, indisprimary::int, indkey, indoption FROM pg_index i, pg_class ci WHERE i.indrelid = $je AND ci.oid = i.indexrelid",$k)as$K){$Ed=$K["relname"];$J[$Ed]["type"]=($K["indisprimary"]?"PRIMARY":($K["indisunique"]?"UNIQUE":"INDEX"));$J[$Ed]["columns"]=array();foreach(explode(" ",$K["indkey"])as$nc)$J[$Ed]["columns"][]=$h[$nc];$J[$Ed]["descs"]=array();foreach(explode(" ",$K["indoption"])as$oc)$J[$Ed]["descs"][]=($oc&1?'1':null);$J[$Ed]["lengths"]=array();}return$J;}function
foreign_keys($Q){global$Zc;$J=array();foreach(get_rows("SELECT conname, pg_get_constraintdef(oid) AS definition
FROM pg_constraint
WHERE conrelid = (SELECT pc.oid FROM pg_class AS pc INNER JOIN pg_namespace AS pn ON (pn.oid = pc.relnamespace) WHERE pc.relname = ".q($Q)." AND pn.nspname = current_schema())
AND contype = 'f'::char
ORDER BY conkey, conname")as$K){if(preg_match('~FOREIGN KEY\s*\((.+)\)\s*REFERENCES (.+)\((.+)\)(.*)$~iA',$K['definition'],$A)){$K['source']=array_map('trim',explode(',',$A[1]));$K['table']=$A[2];if(preg_match('~(.+)\.(.+)~',$A[2],$Ic)){$K['ns']=$Ic[1];$K['table']=$Ic[2];}$K['target']=array_map('trim',explode(',',$A[3]));$K['on_delete']=(preg_match("~ON DELETE ($Zc)~",$A[4],$Ic)?$Ic[1]:'NO ACTION');$K['on_update']=(preg_match("~ON UPDATE ($Zc)~",$A[4],$Ic)?$Ic[1]:'NO ACTION');$J[$K['conname']]=$K;}}return$J;}function
view($C){global$j;return
array("select"=>$j->result("SELECT pg_get_viewdef(".q($C).")"));}function
collations(){return
array();}function
information_schema($n){return($n=="information_schema");}function
error(){global$j;$J=h($j->error);if(preg_match('~^(.*\\n)?([^\\n]*)\\n( *)\\^(\\n.*)?$~s',$J,$A))$J=$A[1].preg_replace('~((?:[^&]|&[^;]*;){'.strlen($A[3]).'})(.*)~','\\1<b>\\2</b>',$A[2]).$A[4];return
nl_br($J);}function
create_database($n,$f){return
queries("CREATE DATABASE ".idf_escape($n).($f?" ENCODING ".idf_escape($f):""));}function
drop_databases($m){global$j;$j->close();return
apply_queries("DROP DATABASE",$m,'idf_escape');}function
rename_database($C,$f){return
queries("ALTER DATABASE ".idf_escape(DB)." RENAME TO ".idf_escape($C));}function
auto_increment(){return"";}function
alter_table($Q,$C,$q,$Mb,$Oa,$sb,$f,$ta,$pd){$d=array();$_d=array();foreach($q
as$p){$g=idf_escape($p[0]);$X=$p[1];if(!$X)$d[]="DROP $g";else{$Ne=$X[5];unset($X[5]);if(isset($X[6])&&$p[0]=="")$X[1]=($X[1]=="bigint"?" big":" ")."serial";if($p[0]=="")$d[]=($Q!=""?"ADD ":"  ").implode($X);else{if($g!=$X[0])$_d[]="ALTER TABLE ".table($Q)." RENAME $g TO $X[0]";$d[]="ALTER $g TYPE$X[1]";if(!$X[6]){$d[]="ALTER $g ".($X[3]?"SET$X[3]":"DROP DEFAULT");$d[]="ALTER $g ".($X[2]==" NULL"?"DROP NOT":"SET").$X[2];}}if($p[0]!=""||$Ne!="")$_d[]="COMMENT ON COLUMN ".table($Q).".$X[0] IS ".($Ne!=""?substr($Ne,9):"''");}}$d=array_merge($d,$Mb);if($Q=="")array_unshift($_d,"CREATE TABLE ".table($C)." (\n".implode(",\n",$d)."\n)");elseif($d)array_unshift($_d,"ALTER TABLE ".table($Q)."\n".implode(",\n",$d));if($Q!=""&&$Q!=$C)$_d[]="ALTER TABLE ".table($Q)." RENAME TO ".table($C);if($Q!=""||$Oa!="")$_d[]="COMMENT ON TABLE ".table($C)." IS ".q($Oa);if($ta!=""){}foreach($_d
as$H){if(!queries($H))return
false;}return
true;}function
alter_indexes($Q,$d){$Ta=array();$hb=array();$_d=array();foreach($d
as$X){if($X[0]!="INDEX")$Ta[]=($X[2]=="DROP"?"\nDROP CONSTRAINT ".idf_escape($X[1]):"\nADD".($X[1]!=""?" CONSTRAINT ".idf_escape($X[1]):"")." $X[0] ".($X[0]=="PRIMARY"?"KEY ":"").$X[2]);elseif($X[2]=="DROP")$hb[]=idf_escape($X[1]);else$_d[]="CREATE INDEX ".idf_escape($X[1]!=""?$X[1]:uniqid($Q."_"))." ON ".table($Q)." $X[2]";}if($Ta)array_unshift($_d,"ALTER TABLE ".table($Q).implode(",",$Ta));if($hb)array_unshift($_d,"DROP INDEX ".implode(", ",$hb));foreach($_d
as$H){if(!queries($H))return
false;}return
true;}function
truncate_tables($S){return
queries("TRUNCATE ".implode(", ",array_map('table',$S)));return
true;}function
drop_views($Qe){return
queries("DROP VIEW ".implode(", ",array_map('table',$Qe)));}function
drop_tables($S){return
queries("DROP TABLE ".implode(", ",array_map('table',$S)));}function
move_tables($S,$Qe,$T){foreach($S
as$Q){if(!queries("ALTER TABLE ".table($Q)." SET SCHEMA ".idf_escape($T)))return
false;}foreach($Qe
as$Q){if(!queries("ALTER VIEW ".table($Q)." SET SCHEMA ".idf_escape($T)))return
false;}return
true;}function
trigger($C){if($C=="")return
array("Statement"=>"EXECUTE PROCEDURE ()");$L=get_rows('SELECT trigger_name AS "Trigger", condition_timing AS "Timing", event_manipulation AS "Event", \'FOR EACH \' || action_orientation AS "Type", action_statement AS "Statement" FROM information_schema.triggers WHERE event_object_table = '.q($_GET["trigger"]).' AND trigger_name = '.q($C));return
reset($L);}function
triggers($Q){$J=array();foreach(get_rows("SELECT * FROM information_schema.triggers WHERE event_object_table = ".q($Q))as$K)$J[$K["trigger_name"]]=array($K["condition_timing"],$K["event_manipulation"]);return$J;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Type"=>array("FOR EACH ROW","FOR EACH STATEMENT"),);}function
routines(){return
get_rows('SELECT p.proname AS "ROUTINE_NAME", p.proargtypes AS "ROUTINE_TYPE", pg_catalog.format_type(p.prorettype, NULL) AS "DTD_IDENTIFIER"
FROM pg_catalog.pg_namespace n
JOIN pg_catalog.pg_proc p ON p.pronamespace = n.oid
WHERE n.nspname = current_schema()
ORDER BY p.proname');}function
routine_languages(){return
get_vals("SELECT langname FROM pg_catalog.pg_language");}function
begin(){return
queries("BEGIN");}function
insert_into($Q,$O){return
queries("INSERT INTO ".table($Q).($O?" (".implode(", ",array_keys($O)).")\nVALUES (".implode(", ",$O).")":"DEFAULT VALUES"));}function
insert_update($Q,$O,$ud){global$j;$Je=array();$Z=array();foreach($O
as$y=>$X){$Je[]="$y = $X";if(isset($ud[idf_unescape($y)]))$Z[]="$y = $X";}return($Z&&queries("UPDATE ".table($Q)." SET ".implode(", ",$Je)." WHERE ".implode(" AND ",$Z))&&$j->affected_rows)||queries("INSERT INTO ".table($Q)." (".implode(", ",array_keys($O)).") VALUES (".implode(", ",$O).")");}function
last_id(){return
0;}function
explain($j,$H){return$j->query("EXPLAIN $H");}function
found_rows($R,$Z){global$j;if(ereg(" rows=([0-9]+)",$j->result("EXPLAIN SELECT * FROM ".idf_escape($R["Name"]).($Z?" WHERE ".implode(" AND ",$Z):"")),$Dd))return$Dd[1];return
false;}function
types(){return
get_vals("SELECT typname
FROM pg_type
WHERE typnamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema())
AND typtype IN ('b','d','e')
AND typelem = 0");}function
schemas(){return
get_vals("SELECT nspname FROM pg_namespace ORDER BY nspname");}function
get_schema(){global$j;return$j->result("SELECT current_schema()");}function
set_schema($Nd){global$j,$Be,$be;$J=$j->query("SET search_path TO ".idf_escape($Nd));foreach(types()as$U){if(!isset($Be[$U])){$Be[$U]=0;$be[lang(13)][]=$U;}}return$J;}function
use_sql($l){return"\connect ".idf_escape($l);}function
show_variables(){return
get_key_vals("SHOW ALL");}function
process_list(){global$j;return
get_rows("SELECT * FROM pg_stat_activity ORDER BY ".($j->server_info<9.2?"procpid":"pid"));}function
show_status(){}function
convert_field($p){}function
unconvert_field($p,$J){return$J;}function
support($Fb){return
ereg('^(comment|view|scheme|processlist|sequence|trigger|type|variables|drop_col)$',$Fb);}$x="pgsql";$Be=array();$be=array();foreach(array(lang(14)=>array("smallint"=>5,"integer"=>10,"bigint"=>19,"boolean"=>1,"numeric"=>0,"real"=>7,"double precision"=>16,"money"=>20),lang(15)=>array("date"=>13,"time"=>17,"timestamp"=>20,"timestamptz"=>21,"interval"=>0),lang(16)=>array("character"=>0,"character varying"=>0,"text"=>0,"tsquery"=>0,"tsvector"=>0,"uuid"=>0,"xml"=>0),lang(17)=>array("bit"=>0,"bit varying"=>0,"bytea"=>0),lang(18)=>array("cidr"=>43,"inet"=>43,"macaddr"=>17,"txid_snapshot"=>0),lang(19)=>array("box"=>0,"circle"=>0,"line"=>0,"lseg"=>0,"path"=>0,"point"=>0,"polygon"=>0),)as$y=>$X){$Be+=$X;$be[$y]=array_keys($X);}$Ie=array();$ed=array("=","<",">","<=",">=","!=","~","!~","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL");$Wb=array("char_length","lower","round","to_hex","to_timestamp","upper");$Zb=array("avg","count","count distinct","max","min","sum");$kb=array(array("char"=>"md5","date|time"=>"now",),array("int|numeric|real|money"=>"+/-","date|time"=>"+ interval/- interval","char|text"=>"||",));}$gb["oracle"]="Oracle";if(isset($_GET["oracle"])){$td=array("OCI8","PDO_OCI");define("DRIVER","oracle");if(extension_loaded("oci8")){class
Min_DB{var$extension="oci8",$_link,$_result,$server_info,$affected_rows,$errno,$error;function
_error($vb,$o){if(ini_bool("html_errors"))$o=html_entity_decode(strip_tags($o));$o=ereg_replace('^[^:]*: ','',$o);$this->error=$o;}function
connect($N,$V,$F){$this->_link=@oci_new_connect($V,$F,$N,"AL32UTF8");if($this->_link){$this->server_info=oci_server_version($this->_link);return
true;}$o=oci_error();$this->error=$o["message"];return
false;}function
quote($P){return"'".str_replace("'","''",$P)."'";}function
select_db($l){return
true;}function
query($H,$Ce=false){$I=oci_parse($this->_link,$H);$this->error="";if(!$I){$o=oci_error($this->_link);$this->errno=$o["code"];$this->error=$o["message"];return
false;}set_error_handler(array($this,'_error'));$J=@oci_execute($I);restore_error_handler();if($J){if(oci_num_fields($I))return
new
Min_Result($I);$this->affected_rows=oci_num_rows($I);}return$J;}function
multi_query($H){return$this->_result=$this->query($H);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($H,$p=1){$I=$this->query($H);if(!is_object($I)||!oci_fetch($I->_result))return
false;return
oci_result($I->_result,$p);}}class
Min_Result{var$_result,$_offset=1,$num_rows;function
Min_Result($I){$this->_result=$I;}function
_convert($K){foreach((array)$K
as$y=>$X){if(is_a($X,'OCI-Lob'))$K[$y]=$X->load();}return$K;}function
fetch_assoc(){return$this->_convert(oci_fetch_assoc($this->_result));}function
fetch_row(){return$this->_convert(oci_fetch_row($this->_result));}function
fetch_field(){$g=$this->_offset++;$J=new
stdClass;$J->name=oci_field_name($this->_result,$g);$J->orgname=$J->name;$J->type=oci_field_type($this->_result,$g);$J->charsetnr=(ereg("raw|blob|bfile",$J->type)?63:0);return$J;}function
__destruct(){oci_free_statement($this->_result);}}}elseif(extension_loaded("pdo_oci")){class
Min_DB
extends
Min_PDO{var$extension="PDO_OCI";function
connect($N,$V,$F){$this->dsn("oci:dbname=//$N;charset=AL32UTF8",$V,$F);return
true;}function
select_db($l){return
true;}}}function
idf_escape($u){return'"'.str_replace('"','""',$u).'"';}function
table($u){return
idf_escape($u);}function
connect(){global$c;$j=new
Min_DB;$Va=$c->credentials();if($j->connect($Va[0],$Va[1],$Va[2]))return$j;return$j->error;}function
get_databases(){return
get_vals("SELECT tablespace_name FROM user_tablespaces");}function
limit($H,$Z,$z,$Xc=0,$Sd=" "){return($Xc?" * FROM (SELECT t.*, rownum AS rnum FROM (SELECT $H$Z) t WHERE rownum <= ".($z+$Xc).") WHERE rnum > $Xc":($z!==null?" * FROM (SELECT $H$Z) WHERE rownum <= ".($z+$Xc):" $H$Z"));}function
limit1($H,$Z){return" $H$Z";}function
db_collation($n,$La){global$j;return$j->result("SELECT value FROM nls_database_parameters WHERE parameter = 'NLS_CHARACTERSET'");}function
engines(){return
array();}function
logged_user(){global$j;return$j->result("SELECT USER FROM DUAL");}function
tables_list(){return
get_key_vals("SELECT table_name, 'table' FROM all_tables WHERE tablespace_name = ".q(DB)."
UNION SELECT view_name, 'view' FROM user_views
ORDER BY 1");}function
count_tables($m){return
array();}function
table_status($C=""){$J=array();$Pd=q($C);foreach(get_rows('SELECT table_name "Name", \'table\' "Engine", avg_row_len * num_rows "Data_length", num_rows "Rows" FROM all_tables WHERE tablespace_name = '.q(DB).($C!=""?" AND table_name = $Pd":"")."
UNION SELECT view_name, 'view', 0, 0 FROM user_views".($C!=""?" WHERE view_name = $Pd":"")."
ORDER BY 1")as$K){if($C!="")return$K;$J[$K["Name"]]=$K;}return$J;}function
is_view($R){return$R["Engine"]=="view";}function
fk_support($R){return
true;}function
fields($Q){$J=array();foreach(get_rows("SELECT * FROM all_tab_columns WHERE table_name = ".q($Q)." ORDER BY column_id")as$K){$U=$K["DATA_TYPE"];$Dc="$K[DATA_PRECISION],$K[DATA_SCALE]";if($Dc==",")$Dc=$K["DATA_LENGTH"];$J[$K["COLUMN_NAME"]]=array("field"=>$K["COLUMN_NAME"],"full_type"=>$U.($Dc?"($Dc)":""),"type"=>strtolower($U),"length"=>$Dc,"default"=>$K["DATA_DEFAULT"],"null"=>($K["NULLABLE"]=="Y"),"privileges"=>array("insert"=>1,"select"=>1,"update"=>1),);}return$J;}function
indexes($Q,$k=null){$J=array();foreach(get_rows("SELECT uic.*, uc.constraint_type
FROM user_ind_columns uic
LEFT JOIN user_constraints uc ON uic.index_name = uc.constraint_name AND uic.table_name = uc.table_name
WHERE uic.table_name = ".q($Q)."
ORDER BY uc.constraint_type, uic.column_position",$k)as$K){$lc=$K["INDEX_NAME"];$J[$lc]["type"]=($K["CONSTRAINT_TYPE"]=="P"?"PRIMARY":($K["CONSTRAINT_TYPE"]=="U"?"UNIQUE":"INDEX"));$J[$lc]["columns"][]=$K["COLUMN_NAME"];$J[$lc]["lengths"][]=($K["CHAR_LENGTH"]&&$K["CHAR_LENGTH"]!=$K["COLUMN_LENGTH"]?$K["CHAR_LENGTH"]:null);$J[$lc]["descs"][]=($K["DESCEND"]?'1':null);}return$J;}function
view($C){$L=get_rows('SELECT text "select" FROM user_views WHERE view_name = '.q($C));return
reset($L);}function
collations(){return
array();}function
information_schema($n){return
false;}function
error(){global$j;return
h($j->error);}function
explain($j,$H){$j->query("EXPLAIN PLAN FOR $H");return$j->query("SELECT * FROM plan_table");}function
found_rows($R,$Z){}function
alter_table($Q,$C,$q,$Mb,$Oa,$sb,$f,$ta,$pd){$d=$hb=array();foreach($q
as$p){$X=$p[1];if($X&&$p[0]!=""&&idf_escape($p[0])!=$X[0])queries("ALTER TABLE ".table($Q)." RENAME COLUMN ".idf_escape($p[0])." TO $X[0]");if($X)$d[]=($Q!=""?($p[0]!=""?"MODIFY (":"ADD ("):"  ").implode($X).($Q!=""?")":"");else$hb[]=idf_escape($p[0]);}if($Q=="")return
queries("CREATE TABLE ".table($C)." (\n".implode(",\n",$d)."\n)");return(!$d||queries("ALTER TABLE ".table($Q)."\n".implode("\n",$d)))&&(!$hb||queries("ALTER TABLE ".table($Q)." DROP (".implode(", ",$hb).")"))&&($Q==$C||queries("ALTER TABLE ".table($Q)." RENAME TO ".table($C)));}function
foreign_keys($Q){return
array();}function
truncate_tables($S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views($Qe){return
apply_queries("DROP VIEW",$Qe);}function
drop_tables($S){return
apply_queries("DROP TABLE",$S);}function
begin(){return
true;}function
insert_into($Q,$O){return
queries("INSERT INTO ".table($Q)." (".implode(", ",array_keys($O)).")\nVALUES (".implode(", ",$O).")");}function
last_id(){return
0;}function
schemas(){return
get_vals("SELECT DISTINCT owner FROM dba_segments WHERE owner IN (SELECT username FROM dba_users WHERE default_tablespace NOT IN ('SYSTEM','SYSAUX'))");}function
get_schema(){global$j;return$j->result("SELECT sys_context('USERENV', 'SESSION_USER') FROM dual");}function
set_schema($Od){global$j;return$j->query("ALTER SESSION SET CURRENT_SCHEMA = ".idf_escape($Od));}function
show_variables(){return
get_key_vals('SELECT name, display_value FROM v$parameter');}function
process_list(){return
get_rows('SELECT sess.process AS "process", sess.username AS "user", sess.schemaname AS "schema", sess.status AS "status", sess.wait_class AS "wait_class", sess.seconds_in_wait AS "seconds_in_wait", sql.sql_text AS "sql_text", sess.machine AS "machine", sess.port AS "port"
FROM v$session sess LEFT OUTER JOIN v$sql sql
ON sql.sql_id = sess.sql_id
WHERE sess.type = \'USER\'
ORDER BY PROCESS
');}function
show_status(){$L=get_rows('SELECT * FROM v$instance');return
reset($L);}function
convert_field($p){}function
unconvert_field($p,$J){return$J;}function
support($Fb){return
ereg("view|scheme|processlist|drop_col|variables|status",$Fb);}$x="oracle";$Be=array();$be=array();foreach(array(lang(14)=>array("number"=>38,"binary_float"=>12,"binary_double"=>21),lang(15)=>array("date"=>10,"timestamp"=>29,"interval year"=>12,"interval day"=>28),lang(16)=>array("char"=>2000,"varchar2"=>4000,"nchar"=>2000,"nvarchar2"=>4000,"clob"=>4294967295,"nclob"=>4294967295),lang(17)=>array("raw"=>2000,"long raw"=>2147483648,"blob"=>4294967295,"bfile"=>4294967296),)as$y=>$X){$Be+=$X;$be[$y]=array_keys($X);}$Ie=array();$ed=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT REGEXP","NOT IN","IS NOT NULL","SQL");$Wb=array("length","lower","round","upper");$Zb=array("avg","count","count distinct","max","min","sum");$kb=array(array("date"=>"current_date","timestamp"=>"current_timestamp",),array("number|float|double"=>"+/-","date|timestamp"=>"+ interval/- interval","char|clob"=>"||",));}$gb["mssql"]="MS SQL";if(isset($_GET["mssql"])){$td=array("SQLSRV","MSSQL");define("DRIVER","mssql");if(extension_loaded("sqlsrv")){class
Min_DB{var$extension="sqlsrv",$_link,$_result,$server_info,$affected_rows,$errno,$error;function
_get_error(){$this->error="";foreach(sqlsrv_errors()as$o){$this->errno=$o["code"];$this->error.="$o[message]\n";}$this->error=rtrim($this->error);}function
connect($N,$V,$F){$this->_link=@sqlsrv_connect($N,array("UID"=>$V,"PWD"=>$F,"CharacterSet"=>"UTF-8"));if($this->_link){$pc=sqlsrv_server_info($this->_link);$this->server_info=$pc['SQLServerVersion'];}else$this->_get_error();return(bool)$this->_link;}function
quote($P){return"'".str_replace("'","''",$P)."'";}function
select_db($l){return$this->query("USE ".idf_escape($l));}function
query($H,$Ce=false){$I=sqlsrv_query($this->_link,$H);$this->error="";if(!$I){$this->_get_error();return
false;}return$this->store_result($I);}function
multi_query($H){$this->_result=sqlsrv_query($this->_link,$H);$this->error="";if(!$this->_result){$this->_get_error();return
false;}return
true;}function
store_result($I=null){if(!$I)$I=$this->_result;if(sqlsrv_field_metadata($I))return
new
Min_Result($I);$this->affected_rows=sqlsrv_rows_affected($I);return
true;}function
next_result(){return
sqlsrv_next_result($this->_result);}function
result($H,$p=0){$I=$this->query($H);if(!is_object($I))return
false;$K=$I->fetch_row();return$K[$p];}}class
Min_Result{var$_result,$_offset=0,$_fields,$num_rows;function
Min_Result($I){$this->_result=$I;}function
_convert($K){foreach((array)$K
as$y=>$X){if(is_a($X,'DateTime'))$K[$y]=$X->format("Y-m-d H:i:s");}return$K;}function
fetch_assoc(){return$this->_convert(sqlsrv_fetch_array($this->_result,SQLSRV_FETCH_ASSOC,SQLSRV_SCROLL_NEXT));}function
fetch_row(){return$this->_convert(sqlsrv_fetch_array($this->_result,SQLSRV_FETCH_NUMERIC,SQLSRV_SCROLL_NEXT));}function
fetch_field(){if(!$this->_fields)$this->_fields=sqlsrv_field_metadata($this->_result);$p=$this->_fields[$this->_offset++];$J=new
stdClass;$J->name=$p["Name"];$J->orgname=$p["Name"];$J->type=($p["Type"]==1?254:0);return$J;}function
seek($Xc){for($t=0;$t<$Xc;$t++)sqlsrv_fetch($this->_result);}function
__destruct(){sqlsrv_free_stmt($this->_result);}}}elseif(extension_loaded("mssql")){class
Min_DB{var$extension="MSSQL",$_link,$_result,$server_info,$affected_rows,$error;function
connect($N,$V,$F){$this->_link=@mssql_connect($N,$V,$F);if($this->_link){$I=$this->query("SELECT SERVERPROPERTY('ProductLevel'), SERVERPROPERTY('Edition')");$K=$I->fetch_row();$this->server_info=$this->result("sp_server_info 2",2)." [$K[0]] $K[1]";}else$this->error=mssql_get_last_message();return(bool)$this->_link;}function
quote($P){return"'".str_replace("'","''",$P)."'";}function
select_db($l){return
mssql_select_db($l);}function
query($H,$Ce=false){$I=mssql_query($H,$this->_link);$this->error="";if(!$I){$this->error=mssql_get_last_message();return
false;}if($I===true){$this->affected_rows=mssql_rows_affected($this->_link);return
true;}return
new
Min_Result($I);}function
multi_query($H){return$this->_result=$this->query($H);}function
store_result(){return$this->_result;}function
next_result(){return
mssql_next_result($this->_result);}function
result($H,$p=0){$I=$this->query($H);if(!is_object($I))return
false;return
mssql_result($I->_result,0,$p);}}class
Min_Result{var$_result,$_offset=0,$_fields,$num_rows;function
Min_Result($I){$this->_result=$I;$this->num_rows=mssql_num_rows($I);}function
fetch_assoc(){return
mssql_fetch_assoc($this->_result);}function
fetch_row(){return
mssql_fetch_row($this->_result);}function
num_rows(){return
mssql_num_rows($this->_result);}function
fetch_field(){$J=mssql_fetch_field($this->_result);$J->orgtable=$J->table;$J->orgname=$J->name;return$J;}function
seek($Xc){mssql_data_seek($this->_result,$Xc);}function
__destruct(){mssql_free_result($this->_result);}}}function
idf_escape($u){return"[".str_replace("]","]]",$u)."]";}function
table($u){return($_GET["ns"]!=""?idf_escape($_GET["ns"]).".":"").idf_escape($u);}function
connect(){global$c;$j=new
Min_DB;$Va=$c->credentials();if($j->connect($Va[0],$Va[1],$Va[2]))return$j;return$j->error;}function
get_databases(){return
get_vals("EXEC sp_databases");}function
limit($H,$Z,$z,$Xc=0,$Sd=" "){return($z!==null?" TOP (".($z+$Xc).")":"")." $H$Z";}function
limit1($H,$Z){return
limit($H,$Z,1);}function
db_collation($n,$La){global$j;return$j->result("SELECT collation_name FROM sys.databases WHERE name =  ".q($n));}function
engines(){return
array();}function
logged_user(){global$j;return$j->result("SELECT SUSER_NAME()");}function
tables_list(){return
get_key_vals("SELECT name, type_desc FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ORDER BY name");}function
count_tables($m){global$j;$J=array();foreach($m
as$n){$j->select_db($n);$J[$n]=$j->result("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES");}return$J;}function
table_status($C=""){$J=array();foreach(get_rows("SELECT name AS Name, type_desc AS Engine FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ".($C!=""?"AND name = ".q($C):"ORDER BY name"))as$K){if($C!="")return$K;$J[$K["Name"]]=$K;}return$J;}function
is_view($R){return$R["Engine"]=="VIEW";}function
fk_support($R){return
true;}function
fields($Q){$J=array();foreach(get_rows("SELECT c.*, t.name type, d.definition [default]
FROM sys.all_columns c
JOIN sys.all_objects o ON c.object_id = o.object_id
JOIN sys.types t ON c.user_type_id = t.user_type_id
LEFT JOIN sys.default_constraints d ON c.default_object_id = d.parent_column_id
WHERE o.schema_id = SCHEMA_ID(".q(get_schema()).") AND o.type IN ('S', 'U', 'V') AND o.name = ".q($Q))as$K){$U=$K["type"];$Dc=(ereg("char|binary",$U)?$K["max_length"]:($U=="decimal"?"$K[precision],$K[scale]":""));$J[$K["name"]]=array("field"=>$K["name"],"full_type"=>$U.($Dc?"($Dc)":""),"type"=>$U,"length"=>$Dc,"default"=>$K["default"],"null"=>$K["is_nullable"],"auto_increment"=>$K["is_identity"],"collation"=>$K["collation_name"],"privileges"=>array("insert"=>1,"select"=>1,"update"=>1),"primary"=>$K["is_identity"],);}return$J;}function
indexes($Q,$k=null){$J=array();foreach(get_rows("SELECT i.name, key_ordinal, is_unique, is_primary_key, c.name AS column_name, is_descending_key
FROM sys.indexes i
INNER JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
INNER JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
WHERE OBJECT_NAME(i.object_id) = ".q($Q),$k)as$K){$C=$K["name"];$J[$C]["type"]=($K["is_primary_key"]?"PRIMARY":($K["is_unique"]?"UNIQUE":"INDEX"));$J[$C]["lengths"]=array();$J[$C]["columns"][$K["key_ordinal"]]=$K["column_name"];$J[$C]["descs"][$K["key_ordinal"]]=($K["is_descending_key"]?'1':null);}return$J;}function
view($C){global$j;return
array("select"=>preg_replace('~^(?:[^[]|\\[[^]]*])*\\s+AS\\s+~isU','',$j->result("SELECT VIEW_DEFINITION FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = SCHEMA_NAME() AND TABLE_NAME = ".q($C))));}function
collations(){$J=array();foreach(get_vals("SELECT name FROM fn_helpcollations()")as$f)$J[ereg_replace("_.*","",$f)][]=$f;return$J;}function
information_schema($n){return
false;}function
error(){global$j;return
nl_br(h(preg_replace('~^(\\[[^]]*])+~m','',$j->error)));}function
create_database($n,$f){return
queries("CREATE DATABASE ".idf_escape($n).(eregi('^[a-z0-9_]+$',$f)?" COLLATE $f":""));}function
drop_databases($m){return
queries("DROP DATABASE ".implode(", ",array_map('idf_escape',$m)));}function
rename_database($C,$f){if(eregi('^[a-z0-9_]+$',$f))queries("ALTER DATABASE ".idf_escape(DB)." COLLATE $f");queries("ALTER DATABASE ".idf_escape(DB)." MODIFY NAME = ".idf_escape($C));return
true;}function
auto_increment(){return" IDENTITY".($_POST["Auto_increment"]!=""?"(".(+$_POST["Auto_increment"]).",1)":"")." PRIMARY KEY";}function
alter_table($Q,$C,$q,$Mb,$Oa,$sb,$f,$ta,$pd){$d=array();foreach($q
as$p){$g=idf_escape($p[0]);$X=$p[1];if(!$X)$d["DROP"][]=" COLUMN $g";else{$X[1]=preg_replace("~( COLLATE )'(\\w+)'~","\\1\\2",$X[1]);if($p[0]=="")$d["ADD"][]="\n  ".implode("",$X).($Q==""?substr($Mb[$X[0]],16+strlen($X[0])):"");else{unset($X[6]);if($g!=$X[0])queries("EXEC sp_rename ".q(table($Q).".$g").", ".q(idf_unescape($X[0])).", 'COLUMN'");$d["ALTER COLUMN ".implode("",$X)][]="";}}}if($Q=="")return
queries("CREATE TABLE ".table($C)." (".implode(",",(array)$d["ADD"])."\n)");if($Q!=$C)queries("EXEC sp_rename ".q(table($Q)).", ".q($C));if($Mb)$d[""]=$Mb;foreach($d
as$y=>$X){if(!queries("ALTER TABLE ".idf_escape($C)." $y".implode(",",$X)))return
false;}return
true;}function
alter_indexes($Q,$d){$v=array();$hb=array();foreach($d
as$X){if($X[2]=="DROP"){if($X[0]=="PRIMARY")$hb[]=idf_escape($X[1]);else$v[]=idf_escape($X[1])." ON ".table($Q);}elseif(!queries(($X[0]!="PRIMARY"?"CREATE $X[0] ".($X[0]!="INDEX"?"INDEX ":"").idf_escape($X[1]!=""?$X[1]:uniqid($Q."_"))." ON ".table($Q):"ALTER TABLE ".table($Q)." ADD PRIMARY KEY")." $X[2]"))return
false;}return(!$v||queries("DROP INDEX ".implode(", ",$v)))&&(!$hb||queries("ALTER TABLE ".table($Q)." DROP ".implode(", ",$hb)));}function
begin(){return
queries("BEGIN TRANSACTION");}function
insert_into($Q,$O){return
queries("INSERT INTO ".table($Q).($O?" (".implode(", ",array_keys($O)).")\nVALUES (".implode(", ",$O).")":"DEFAULT VALUES"));}function
insert_update($Q,$O,$ud){$Je=array();$Z=array();foreach($O
as$y=>$X){$Je[]="$y = $X";if(isset($ud[idf_unescape($y)]))$Z[]="$y = $X";}return
queries("MERGE ".table($Q)." USING (VALUES(".implode(", ",$O).")) AS source (c".implode(", c",range(1,count($O))).") ON ".implode(" AND ",$Z)." WHEN MATCHED THEN UPDATE SET ".implode(", ",$Je)." WHEN NOT MATCHED THEN INSERT (".implode(", ",array_keys($O)).") VALUES (".implode(", ",$O).");");}function
last_id(){global$j;return$j->result("SELECT SCOPE_IDENTITY()");}function
explain($j,$H){$j->query("SET SHOWPLAN_ALL ON");$J=$j->query($H);$j->query("SET SHOWPLAN_ALL OFF");return$J;}function
found_rows($R,$Z){}function
foreign_keys($Q){$J=array();foreach(get_rows("EXEC sp_fkeys @fktable_name = ".q($Q))as$K){$r=&$J[$K["FK_NAME"]];$r["table"]=$K["PKTABLE_NAME"];$r["source"][]=$K["FKCOLUMN_NAME"];$r["target"][]=$K["PKCOLUMN_NAME"];}return$J;}function
truncate_tables($S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views($Qe){return
queries("DROP VIEW ".implode(", ",array_map('table',$Qe)));}function
drop_tables($S){return
queries("DROP TABLE ".implode(", ",array_map('table',$S)));}function
move_tables($S,$Qe,$T){return
apply_queries("ALTER SCHEMA ".idf_escape($T)." TRANSFER",array_merge($S,$Qe));}function
trigger($C){if($C=="")return
array();$L=get_rows("SELECT s.name [Trigger],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT' WHEN OBJECTPROPERTY(s.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE' WHEN OBJECTPROPERTY(s.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing],
c.text
FROM sysobjects s
JOIN syscomments c ON s.id = c.id
WHERE s.xtype = 'TR' AND s.name = ".q($C));$J=reset($L);if($J)$J["Statement"]=preg_replace('~^.+\\s+AS\\s+~isU','',$J["text"]);return$J;}function
triggers($Q){$J=array();foreach(get_rows("SELECT sys1.name,
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT' WHEN OBJECTPROPERTY(sys1.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE' WHEN OBJECTPROPERTY(sys1.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing]
FROM sysobjects sys1
JOIN sysobjects sys2 ON sys1.parent_obj = sys2.id
WHERE sys1.xtype = 'TR' AND sys2.name = ".q($Q))as$K)$J[$K["name"]]=array($K["Timing"],$K["Event"]);return$J;}function
trigger_options(){return
array("Timing"=>array("AFTER","INSTEAD OF"),"Type"=>array("AS"),);}function
schemas(){return
get_vals("SELECT name FROM sys.schemas");}function
get_schema(){global$j;if($_GET["ns"]!="")return$_GET["ns"];return$j->result("SELECT SCHEMA_NAME()");}function
set_schema($Nd){return
true;}function
use_sql($l){return"USE ".idf_escape($l);}function
show_variables(){return
array();}function
show_status(){return
array();}function
convert_field($p){}function
unconvert_field($p,$J){return$J;}function
support($Fb){return
ereg('^(scheme|trigger|view|drop_col)$',$Fb);}$x="mssql";$Be=array();$be=array();foreach(array(lang(14)=>array("tinyint"=>3,"smallint"=>5,"int"=>10,"bigint"=>20,"bit"=>1,"decimal"=>0,"real"=>12,"float"=>53,"smallmoney"=>10,"money"=>20),lang(15)=>array("date"=>10,"smalldatetime"=>19,"datetime"=>19,"datetime2"=>19,"time"=>8,"datetimeoffset"=>10),lang(16)=>array("char"=>8000,"varchar"=>8000,"text"=>2147483647,"nchar"=>4000,"nvarchar"=>4000,"ntext"=>1073741823),lang(17)=>array("binary"=>8000,"varbinary"=>8000,"image"=>2147483647),)as$y=>$X){$Be+=$X;$be[$y]=array_keys($X);}$Ie=array();$ed=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL");$Wb=array("len","lower","round","upper");$Zb=array("avg","count","count distinct","max","min","sum");$kb=array(array("date|time"=>"getdate",),array("int|decimal|real|float|money|datetime"=>"+/-","char|text"=>"+",));}$gb=array("server"=>"MySQL")+$gb;if(!defined("DRIVER")){$td=array("MySQLi","MySQL","PDO_MySQL");define("DRIVER","server");if(extension_loaded("mysqli")){class
Min_DB
extends
MySQLi{var$extension="MySQLi";function
Min_DB(){parent::init();}function
connect($N,$V,$F){mysqli_report(MYSQLI_REPORT_OFF);list($fc,$rd)=explode(":",$N,2);$J=@$this->real_connect(($N!=""?$fc:ini_get("mysqli.default_host")),($N.$V!=""?$V:ini_get("mysqli.default_user")),($N.$V.$F!=""?$F:ini_get("mysqli.default_pw")),null,(is_numeric($rd)?$rd:ini_get("mysqli.default_port")),(!is_numeric($rd)?$rd:null));if($J){if(method_exists($this,'set_charset'))$this->set_charset("utf8");else$this->query("SET NAMES utf8");}return$J;}function
result($H,$p=0){$I=$this->query($H);if(!$I)return
false;$K=$I->fetch_array();return$K[$p];}function
quote($P){return"'".$this->escape_string($P)."'";}}}elseif(extension_loaded("mysql")&&!(ini_get("sql.safe_mode")&&extension_loaded("pdo_mysql"))){class
Min_DB{var$extension="MySQL",$server_info,$affected_rows,$errno,$error,$_link,$_result;function
connect($N,$V,$F){$this->_link=@mysql_connect(($N!=""?$N:ini_get("mysql.default_host")),("$N$V"!=""?$V:ini_get("mysql.default_user")),("$N$V$F"!=""?$F:ini_get("mysql.default_password")),true,131072);if($this->_link){$this->server_info=mysql_get_server_info($this->_link);if(function_exists('mysql_set_charset'))mysql_set_charset("utf8",$this->_link);else$this->query("SET NAMES utf8");}else$this->error=mysql_error();return(bool)$this->_link;}function
quote($P){return"'".mysql_real_escape_string($P,$this->_link)."'";}function
select_db($l){return
mysql_select_db($l,$this->_link);}function
query($H,$Ce=false){$I=@($Ce?mysql_unbuffered_query($H,$this->_link):mysql_query($H,$this->_link));$this->error="";if(!$I){$this->errno=mysql_errno($this->_link);$this->error=mysql_error($this->_link);return
false;}if($I===true){$this->affected_rows=mysql_affected_rows($this->_link);$this->info=mysql_info($this->_link);return
true;}return
new
Min_Result($I);}function
multi_query($H){return$this->_result=$this->query($H);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($H,$p=0){$I=$this->query($H);if(!$I||!$I->num_rows)return
false;return
mysql_result($I->_result,0,$p);}}class
Min_Result{var$num_rows,$_result,$_offset=0;function
Min_Result($I){$this->_result=$I;$this->num_rows=mysql_num_rows($I);}function
fetch_assoc(){return
mysql_fetch_assoc($this->_result);}function
fetch_row(){return
mysql_fetch_row($this->_result);}function
fetch_field(){$J=mysql_fetch_field($this->_result,$this->_offset++);$J->orgtable=$J->table;$J->orgname=$J->name;$J->charsetnr=($J->blob?63:0);return$J;}function
__destruct(){mysql_free_result($this->_result);}}}elseif(extension_loaded("pdo_mysql")){class
Min_DB
extends
Min_PDO{var$extension="PDO_MySQL";function
connect($N,$V,$F){$this->dsn("mysql:host=".str_replace(":",";unix_socket=",preg_replace('~:(\\d)~',';port=\\1',$N)),$V,$F);$this->query("SET NAMES utf8");return
true;}function
select_db($l){return$this->query("USE ".idf_escape($l));}function
query($H,$Ce=false){$this->setAttribute(1000,!$Ce);return
parent::query($H,$Ce);}}}function
idf_escape($u){return"`".str_replace("`","``",$u)."`";}function
table($u){return
idf_escape($u);}function
connect(){global$c;$j=new
Min_DB;$Va=$c->credentials();if($j->connect($Va[0],$Va[1],$Va[2])){$j->query("SET sql_quote_show_create = 1, autocommit = 1");return$j;}$J=$j->error;if(function_exists('iconv')&&!is_utf8($J)&&strlen($Md=iconv("windows-1250","utf-8",$J))>strlen($J))$J=$Md;return$J;}function
get_databases($Lb){global$j;$J=get_session_adminer("dbs");if($J===null){$H=($j->server_info>=5?"SELECT SCHEMA_NAME FROM information_schema.SCHEMATA":"SHOW DATABASES");$J=($Lb?slow_query($H):get_vals($H));restart_session();set_session("dbs",$J);stop_session();}return$J;}function
limit($H,$Z,$z,$Xc=0,$Sd=" "){return" $H$Z".($z!==null?$Sd."LIMIT $z".($Xc?" OFFSET $Xc":""):"");}function
limit1($H,$Z){return
limit($H,$Z,1);}function
db_collation($n,$La){global$j;$J=null;$Ta=$j->result("SHOW CREATE DATABASE ".idf_escape($n),1);if(preg_match('~ COLLATE ([^ ]+)~',$Ta,$A))$J=$A[1];elseif(preg_match('~ CHARACTER SET ([^ ]+)~',$Ta,$A))$J=$La[$A[1]][-1];return$J;}function
engines(){$J=array();foreach(get_rows("SHOW ENGINES")as$K){if(ereg("YES|DEFAULT",$K["Support"]))$J[]=$K["Engine"];}return$J;}function
logged_user(){global$j;return$j->result("SELECT USER()");}function
tables_list(){global$j;return
get_key_vals("SHOW".($j->server_info>=5?" FULL":"")." TABLES");}function
count_tables($m){$J=array();foreach($m
as$n)$J[$n]=count(get_vals("SHOW TABLES IN ".idf_escape($n)));return$J;}function
table_status($C="",$Eb=false){global$j;$J=array();foreach(get_rows($Eb&&$j->server_info>=5?"SELECT TABLE_NAME AS Name, Engine, TABLE_COMMENT AS Comment FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ".($C!=""?"AND TABLE_NAME = ".q($C):"ORDER BY Name"):"SHOW TABLE STATUS".($C!=""?" LIKE ".q(addcslashes($C,"%_\\")):""))as$K){if($K["Engine"]=="InnoDB")$K["Comment"]=preg_replace('~(?:(.+); )?InnoDB free: .*~','\\1',$K["Comment"]);if(!isset($K["Engine"]))$K["Comment"]="";if($C!="")return$K;$J[$K["Name"]]=$K;}return$J;}function
is_view($R){return$R["Engine"]===null;}function
fk_support($R){return
eregi("InnoDB|IBMDB2I",$R["Engine"]);}function
fields($Q){$J=array();foreach(get_rows("SHOW FULL COLUMNS FROM ".table($Q))as$K){preg_match('~^([^( ]+)(?:\\((.+)\\))?( unsigned)?( zerofill)?$~',$K["Type"],$A);$J[$K["Field"]]=array("field"=>$K["Field"],"full_type"=>$K["Type"],"type"=>$A[1],"length"=>$A[2],"unsigned"=>ltrim($A[3].$A[4]),"default"=>($K["Default"]!=""||ereg("char|set",$A[1])?$K["Default"]:null),"null"=>($K["Null"]=="YES"),"auto_increment"=>($K["Extra"]=="auto_increment"),"on_update"=>(eregi('^on update (.+)',$K["Extra"],$A)?$A[1]:""),"collation"=>$K["Collation"],"privileges"=>array_flip(explode(",",$K["Privileges"])),"comment"=>$K["Comment"],"primary"=>($K["Key"]=="PRI"),);}return$J;}function
indexes($Q,$k=null){$J=array();foreach(get_rows("SHOW INDEX FROM ".table($Q),$k)as$K){$J[$K["Key_name"]]["type"]=($K["Key_name"]=="PRIMARY"?"PRIMARY":($K["Index_type"]=="FULLTEXT"?"FULLTEXT":($K["Non_unique"]?"INDEX":"UNIQUE")));$J[$K["Key_name"]]["columns"][]=$K["Column_name"];$J[$K["Key_name"]]["lengths"][]=$K["Sub_part"];$J[$K["Key_name"]]["descs"][]=null;}return$J;}function
foreign_keys($Q){global$j,$Zc;static$G='`(?:[^`]|``)+`';$J=array();$Ua=$j->result("SHOW CREATE TABLE ".table($Q),1);if($Ua){preg_match_all("~CONSTRAINT ($G) FOREIGN KEY \\(((?:$G,? ?)+)\\) REFERENCES ($G)(?:\\.($G))? \\(((?:$G,? ?)+)\\)(?: ON DELETE ($Zc))?(?: ON UPDATE ($Zc))?~",$Ua,$Jc,PREG_SET_ORDER);foreach($Jc
as$A){preg_match_all("~$G~",$A[2],$Vd);preg_match_all("~$G~",$A[5],$T);$J[idf_unescape($A[1])]=array("db"=>idf_unescape($A[4]!=""?$A[3]:$A[4]),"table"=>idf_unescape($A[4]!=""?$A[4]:$A[3]),"source"=>array_map('idf_unescape',$Vd[0]),"target"=>array_map('idf_unescape',$T[0]),"on_delete"=>($A[6]?$A[6]:"RESTRICT"),"on_update"=>($A[7]?$A[7]:"RESTRICT"),);}}return$J;}function
view($C){global$j;return
array("select"=>preg_replace('~^(?:[^`]|`[^`]*`)*\\s+AS\\s+~isU','',$j->result("SHOW CREATE VIEW ".table($C),1)));}function
collations(){$J=array();foreach(get_rows("SHOW COLLATION")as$K){if($K["Default"])$J[$K["Charset"]][-1]=$K["Collation"];else$J[$K["Charset"]][]=$K["Collation"];}ksort($J);foreach($J
as$y=>$X)asort($J[$y]);return$J;}function
information_schema($n){global$j;return($j->server_info>=5&&$n=="information_schema")||($j->server_info>=5.5&&$n=="performance_schema");}function
error(){global$j;return
h(preg_replace('~^You have an error.*syntax to use~U',"Syntax error",$j->error));}function
error_line(){global$j;if(ereg(' at line ([0-9]+)$',$j->error,$Dd))return$Dd[1]-1;}function
create_database($n,$f){set_session("dbs",null);return
queries("CREATE DATABASE ".idf_escape($n).($f?" COLLATE ".q($f):""));}function
drop_databases($m){restart_session();set_session("dbs",null);return
apply_queries("DROP DATABASE",$m,'idf_escape');}function
rename_database($C,$f){if(create_database($C,$f)){$Fd=array();foreach(tables_list()as$Q=>$U)$Fd[]=table($Q)." TO ".idf_escape($C).".".table($Q);if(!$Fd||queries("RENAME TABLE ".implode(", ",$Fd))){queries("DROP DATABASE ".idf_escape(DB));return
true;}}return
false;}function
auto_increment(){$ua=" PRIMARY KEY";if($_GET["create"]!=""&&$_POST["auto_increment_col"]){foreach(indexes($_GET["create"])as$v){if(in_array($_POST["fields"][$_POST["auto_increment_col"]]["orig"],$v["columns"],true)){$ua="";break;}if($v["type"]=="PRIMARY")$ua=" UNIQUE";}}return" AUTO_INCREMENT$ua";}function
alter_table($Q,$C,$q,$Mb,$Oa,$sb,$f,$ta,$pd){$d=array();foreach($q
as$p)$d[]=($p[1]?($Q!=""?($p[0]!=""?"CHANGE ".idf_escape($p[0]):"ADD"):" ")." ".implode($p[1]).($Q!=""?$p[2]:""):"DROP ".idf_escape($p[0]));$d=array_merge($d,$Mb);$Zd="COMMENT=".q($Oa).($sb?" ENGINE=".q($sb):"").($f?" COLLATE ".q($f):"").($ta!=""?" AUTO_INCREMENT=$ta":"").$pd;if($Q=="")return
queries("CREATE TABLE ".table($C)." (\n".implode(",\n",$d)."\n) $Zd");if($Q!=$C)$d[]="RENAME TO ".table($C);$d[]=$Zd;return
queries("ALTER TABLE ".table($Q)."\n".implode(",\n",$d));}function
alter_indexes($Q,$d){foreach($d
as$y=>$X)$d[$y]=($X[2]=="DROP"?"\nDROP INDEX ".idf_escape($X[1]):"\nADD $X[0] ".($X[0]=="PRIMARY"?"KEY ":"").($X[1]!=""?idf_escape($X[1])." ":"").$X[2]);return
queries("ALTER TABLE ".table($Q).implode(",",$d));}function
truncate_tables($S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views($Qe){return
queries("DROP VIEW ".implode(", ",array_map('table',$Qe)));}function
drop_tables($S){return
queries("DROP TABLE ".implode(", ",array_map('table',$S)));}function
move_tables($S,$Qe,$T){$Fd=array();foreach(array_merge($S,$Qe)as$Q)$Fd[]=table($Q)." TO ".idf_escape($T).".".table($Q);return
queries("RENAME TABLE ".implode(", ",$Fd));}function
copy_tables($S,$Qe,$T){queries("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");foreach($S
as$Q){$C=($T==DB?table("copy_$Q"):idf_escape($T).".".table($Q));if(!queries("DROP TABLE IF EXISTS $C")||!queries("CREATE TABLE $C LIKE ".table($Q))||!queries("INSERT INTO $C SELECT * FROM ".table($Q)))return
false;}foreach($Qe
as$Q){$C=($T==DB?table("copy_$Q"):idf_escape($T).".".table($Q));$Pe=view($Q);if(!queries("DROP VIEW IF EXISTS $C")||!queries("CREATE VIEW $C AS $Pe[select]"))return
false;}return
true;}function
trigger($C){if($C=="")return
array();$L=get_rows("SHOW TRIGGERS WHERE `Trigger` = ".q($C));return
reset($L);}function
triggers($Q){$J=array();foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($Q,"%_\\")))as$K)$J[$K["Trigger"]]=array($K["Timing"],$K["Event"]);return$J;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Type"=>array("FOR EACH ROW"),);}function
routine($C,$U){global$j,$tb,$rc,$Be;$na=array("bool","boolean","integer","double precision","real","dec","numeric","fixed","national char","national varchar");$Ae="((".implode("|",array_merge(array_keys($Be),$na)).")\\b(?:\\s*\\(((?:[^'\")]*|$tb)+)\\))?\\s*(zerofill\\s*)?(unsigned(?:\\s+zerofill)?)?)(?:\\s*(?:CHARSET|CHARACTER\\s+SET)\\s*['\"]?([^'\"\\s]+)['\"]?)?";$G="\\s*(".($U=="FUNCTION"?"":$rc).")?\\s*(?:`((?:[^`]|``)*)`\\s*|\\b(\\S+)\\s+)$Ae";$Ta=$j->result("SHOW CREATE $U ".idf_escape($C),2);preg_match("~\\(((?:$G\\s*,?)*)\\)\\s*".($U=="FUNCTION"?"RETURNS\\s+$Ae\\s+":"")."(.*)~is",$Ta,$A);$q=array();preg_match_all("~$G\\s*,?~is",$A[1],$Jc,PREG_SET_ORDER);foreach($Jc
as$nd){$C=str_replace("``","`",$nd[2]).$nd[3];$q[]=array("field"=>$C,"type"=>strtolower($nd[5]),"length"=>preg_replace_callback("~$tb~s",'normalize_enum',$nd[6]),"unsigned"=>strtolower(preg_replace('~\\s+~',' ',trim("$nd[8] $nd[7]"))),"null"=>1,"full_type"=>$nd[4],"inout"=>strtoupper($nd[1]),"collation"=>strtolower($nd[9]),);}if($U!="FUNCTION")return
array("fields"=>$q,"definition"=>$A[11]);return
array("fields"=>$q,"returns"=>array("type"=>$A[12],"length"=>$A[13],"unsigned"=>$A[15],"collation"=>$A[16]),"definition"=>$A[17],"language"=>"SQL",);}function
routines(){return
get_rows("SELECT ROUTINE_NAME, ROUTINE_TYPE, DTD_IDENTIFIER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = ".q(DB));}function
routine_languages(){return
array();}function
begin(){return
queries("BEGIN");}function
insert_into($Q,$O){return
queries("INSERT INTO ".table($Q)." (".implode(", ",array_keys($O)).")\nVALUES (".implode(", ",$O).")");}function
insert_update($Q,$O,$ud){foreach($O
as$y=>$X)$O[$y]="$y = $X";$Je=implode(", ",$O);return
queries("INSERT INTO ".table($Q)." SET $Je ON DUPLICATE KEY UPDATE $Je");}function
last_id(){global$j;return$j->result("SELECT LAST_INSERT_ID()");}function
explain($j,$H){return$j->query("EXPLAIN ".($j->server_info>=5.1?"PARTITIONS ":"").$H);}function
found_rows($R,$Z){return($Z||$R["Engine"]!="InnoDB"?null:$R["Rows"]);}function
types(){return
array();}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($Nd){return
true;}function
create_sql($Q,$ta){global$j;$J=$j->result("SHOW CREATE TABLE ".table($Q),1);if(!$ta)$J=preg_replace('~ AUTO_INCREMENT=\\d+~','',$J);return$J;}function
truncate_sql($Q){return"TRUNCATE ".table($Q);}function
use_sql($l){return"USE ".idf_escape($l);}function
trigger_sql($Q,$ce){$J="";foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($Q,"%_\\")),null,"-- ")as$K)$J.="\n".($ce=='CREATE+ALTER'?"DROP TRIGGER IF EXISTS ".idf_escape($K["Trigger"]).";;\n":"")."CREATE TRIGGER ".idf_escape($K["Trigger"])." $K[Timing] $K[Event] ON ".table($K["Table"])." FOR EACH ROW\n$K[Statement];;\n";return$J;}function
show_variables(){return
get_key_vals("SHOW VARIABLES");}function
process_list(){return
get_rows("SHOW FULL PROCESSLIST");}function
show_status(){return
get_key_vals("SHOW STATUS");}function
convert_field($p){if(ereg("binary",$p["type"]))return"HEX(".idf_escape($p["field"]).")";if($p["type"]=="bit")return"BIN(".idf_escape($p["field"])." + 0)";if(ereg("geometry|point|linestring|polygon",$p["type"]))return"AsWKT(".idf_escape($p["field"]).")";}function
unconvert_field($p,$J){if(ereg("binary",$p["type"]))$J="UNHEX($J)";if($p["type"]=="bit")$J="CONV($J, 2, 10) + 0";if(ereg("geometry|point|linestring|polygon",$p["type"]))$J="GeomFromText($J)";return$J;}function
support($Fb){global$j;return!ereg("scheme|sequence|type".($j->server_info<5.1?"|event|partitioning".($j->server_info<5?"|view|routine|trigger":""):""),$Fb);}$x="sql";$Be=array();$be=array();foreach(array(lang(14)=>array("tinyint"=>3,"smallint"=>5,"mediumint"=>8,"int"=>10,"bigint"=>20,"decimal"=>66,"float"=>12,"double"=>21),lang(15)=>array("date"=>10,"datetime"=>19,"timestamp"=>19,"time"=>10,"year"=>4),lang(16)=>array("char"=>255,"varchar"=>65535,"tinytext"=>255,"text"=>65535,"mediumtext"=>16777215,"longtext"=>4294967295),lang(20)=>array("enum"=>65535,"set"=>64),lang(17)=>array("bit"=>20,"binary"=>255,"varbinary"=>65535,"tinyblob"=>255,"blob"=>65535,"mediumblob"=>16777215,"longblob"=>4294967295),lang(19)=>array("geometry"=>0,"point"=>0,"linestring"=>0,"polygon"=>0,"multipoint"=>0,"multilinestring"=>0,"multipolygon"=>0,"geometrycollection"=>0),)as$y=>$X){$Be+=$X;$be[$y]=array_keys($X);}$Ie=array("unsigned","zerofill","unsigned zerofill");$ed=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","REGEXP","IN","IS NULL","NOT LIKE","NOT REGEXP","NOT IN","IS NOT NULL","SQL");$Wb=array("char_length","date","from_unixtime","lower","round","sec_to_time","time_to_sec","upper");$Zb=array("avg","count","count distinct","group_concat","max","min","sum");$kb=array(array("char"=>"md5/sha1/password/encrypt/uuid","binary"=>"md5/sha1","date|time"=>"now",),array("(^|[^o])int|float|double|decimal"=>"+/-","date"=>"+ interval/- interval","time"=>"addtime/subtime","char|text"=>"concat",));}define("SERVER",$_GET[DRIVER]);define("DB",$_GET["db"]);define("ME",preg_replace('~^[^?]*/([^?]*).*~','\\1',$_SERVER["REQUEST_URI"]).'?'.(sid()?SID.'&':'').(SERVER!==null?DRIVER."=".urlencode(SERVER).'&':'').(isset($_GET["username"])?"username=".urlencode($_GET["username"]).'&':'').(DB!=""?'db='.urlencode(DB).'&'.(isset($_GET["ns"])?"ns=".urlencode($_GET["ns"])."&":""):''));$ba="3.7.1";class
Adminer{var$operators=array("<=",">=");var$_values=array();function
name(){return"<a href='http://www.adminer.org/editor/' id='h1'>".lang(21)."</a>";}function
credentials(){return
array(SERVER,$_GET["username"],get_session_adminer("pwds"));}function
permanentLogin($Ta=false){return
password_file($Ta);}function
database(){global$j;if($j){$m=$this->databases(false);return(!$m?$j->result("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', 1)"):$m[(information_schema($m[0])?1:0)]);}}function
databases($Lb=true){return
get_databases($Lb);}function
queryTimeout(){return
5;}function
headers(){return
true;}function
head(){return
true;}function
loginForm(){echo'<table cellspacing="0">
<tr><th>',lang(22),'<td><input type="hidden" name="auth[driver]" value="server"><input name="auth[username]" id="username" value="',h($_GET["username"]),'" autocapitalize="off">
<tr><th>',lang(23),'<td><input type="password" name="auth[password]">
</table>
<script type="text/javascript">
focus(document.getElementById(\'username\'));
</script>
',"<p><input type='submit' value='".lang(24)."'>\n",checkbox("auth[permanent]",1,$_COOKIE["adminer_permanent"],lang(25))."\n";}function
login($Gc,$F){global$j;$j->query("SET time_zone = ".q(substr_replace(@date("O"),":",-2,0)));return
true;}function
tableName($he){return
h($he["Comment"]!=""?$he["Comment"]:$he["Name"]);}function
fieldName($p,$id=0){return
h($p["comment"]!=""?$p["comment"]:$p["field"]);}function
selectLinks($he,$O=""){$b=$he["Name"];if($O!==null)echo'<p class="tabs"><a href="'.h(ME.'edit='.urlencode($b).$O).'">'.lang(26)."</a>\n";}function
foreignKeys($Q){return
foreign_keys($Q);}function
backwardKeys($Q,$ge){$J=array();foreach(get_rows("SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = ".q($this->database())."
AND REFERENCED_TABLE_SCHEMA = ".q($this->database())."
AND REFERENCED_TABLE_NAME = ".q($Q)."
ORDER BY ORDINAL_POSITION",null,"")as$K)$J[$K["TABLE_NAME"]]["keys"][$K["CONSTRAINT_NAME"]][$K["COLUMN_NAME"]]=$K["REFERENCED_COLUMN_NAME"];foreach($J
as$y=>$X){$C=$this->tableName(table_status($y,true));if($C!=""){$Pd=preg_quote($ge);$Sd="(:|\\s*-)?\\s+";$J[$y]["name"]=(preg_match("(^$Pd$Sd(.+)|^(.+?)$Sd$Pd\$)iu",$C,$A)?$A[2].$A[3]:$C);}else
unset($J[$y]);}return$J;}function
backwardKeysPrint($xa,$K){foreach($xa
as$Q=>$wa){foreach($wa["keys"]as$Ma){$_=ME.'select='.urlencode($Q);$t=0;foreach($Ma
as$g=>$X)$_.=where_link($t++,$g,$K[$X]);echo"<a href='".h($_)."'>".h($wa["name"])."</a>";$_=ME.'edit='.urlencode($Q);foreach($Ma
as$g=>$X)$_.="&set".urlencode("[".bracket_escape($g)."]")."=".urlencode($K[$X]);echo"<a href='".h($_)."' title='".lang(26)."'>+</a> ";}}}function
selectQuery($H){return"<!--\n".str_replace("--","--><!-- ",$H)."\n-->\n";}function
rowDescription($Q){foreach(fields($Q)as$p){if(ereg("varchar|character varying",$p["type"]))return
idf_escape($p["field"]);}return"";}function
rowDescriptions($L,$Ob){$J=$L;foreach($L[0]as$y=>$X){if(list($Q,$hc,$C)=$this->_foreignColumn($Ob,$y)){$jc=array();foreach($L
as$K)$jc[$K[$y]]=q($K[$y]);$ab=$this->_values[$Q];if(!$ab)$ab=get_key_vals("SELECT $hc, $C FROM ".table($Q)." WHERE $hc IN (".implode(", ",$jc).")");foreach($L
as$B=>$K){if(isset($K[$y]))$J[$B][$y]=(string)$ab[$K[$y]];}}}return$J;}function
selectLink($X,$p){}function
selectVal($X,$_,$p){$J=($X===null?"&nbsp;":$X);$_=h($_);if(ereg('blob|bytea',$p["type"])&&!is_utf8($X)){$J=lang(27,strlen($X));if(ereg("^(GIF|\xFF\xD8\xFF|\x89PNG\x0D\x0A\x1A\x0A)",$X))$J="<img src='$_' alt='$J'>";}if(like_bool($p)&&$J!="&nbsp;")$J=($X?lang(28):lang(29));if($_)$J="<a href='$_'>$J</a>";if(!$_&&!like_bool($p)&&ereg('int|float|double|decimal',$p["type"]))$J="<div class='number'>$J</div>";elseif(ereg('date',$p["type"]))$J="<div class='datetime'>$J</div>";return$J;}function
editVal($X,$p){if(ereg('date|timestamp',$p["type"])&&$X!==null)return
preg_replace('~^(\\d{2}(\\d+))-(0?(\\d+))-(0?(\\d+))~',lang(30),$X);return$X;}function
selectColumnsPrint($M,$h){}function
selectSearchPrint($Z,$h,$w){$Z=(array)$_GET["where"];echo'<fieldset id="fieldset-search"><legend>'.lang(31)."</legend><div>\n";$wc=array();foreach($Z
as$y=>$X)$wc[$X["col"]]=$y;$t=0;$q=fields($_GET["select"]);foreach($h
as$C=>$Za){$p=$q[$C];if(ereg("enum",$p["type"])||like_bool($p)){$y=$wc[$C];$t--;echo"<div>".h($Za)."<input type='hidden' name='where[$t][col]' value='".h($C)."'>:",(like_bool($p)?" <select name='where[$t][val]'>".optionlist(array(""=>"",lang(29),lang(28)),$Z[$y]["val"],true)."</select>":enum_input("checkbox"," name='where[$t][val][]'",$p,(array)$Z[$y]["val"],($p["null"]?0:null))),"</div>\n";unset($h[$C]);}elseif(is_array($gd=$this->_foreignKeyOptions($_GET["select"],$C))){if($q[$C]["null"])$gd[0]='('.lang(5).')';$y=$wc[$C];$t--;echo"<div>".h($Za)."<input type='hidden' name='where[$t][col]' value='".h($C)."'><input type='hidden' name='where[$t][op]' value='='>: <select name='where[$t][val]'>".optionlist($gd,$Z[$y]["val"],true)."</select></div>\n";unset($h[$C]);}}$t=0;foreach($Z
as$X){if(($X["col"]==""||$h[$X["col"]])&&"$X[col]$X[val]"!=""){echo"<div><select name='where[$t][col]'><option value=''>(".lang(32).")".optionlist($h,$X["col"],true)."</select>",html_select("where[$t][op]",array(-1=>"")+$this->operators,$X["op"]),"<input type='search' name='where[$t][val]' value='".h($X["val"])."' onsearch='selectSearchSearch(this);'></div>\n";$t++;}}echo"<div><select name='where[$t][col]' onchange='this.nextSibling.nextSibling.onchange();'><option value=''>(".lang(32).")".optionlist($h,null,true)."</select>",html_select("where[$t][op]",array(-1=>"")+$this->operators),"<input type='search' name='where[$t][val]' onchange='selectAddRow(this);' onsearch='selectSearch(this);'></div>\n","</div></fieldset>\n";}function
selectOrderPrint($id,$h,$w){$jd=array();foreach($w
as$y=>$v){$id=array();foreach($v["columns"]as$X)$id[]=$h[$X];if(count(array_filter($id,'strlen'))>1&&$y!="PRIMARY")$jd[$y]=implode(", ",$id);}if($jd){echo'<fieldset><legend>'.lang(33)."</legend><div>","<select name='index_order'>".optionlist(array(""=>"")+$jd,($_GET["order"][0]!=""?"":$_GET["index_order"]),true)."</select>","</div></fieldset>\n";}if($_GET["order"])echo"<div style='display: none;'>".hidden_fields(array("order"=>array(1=>reset($_GET["order"])),"desc"=>($_GET["desc"]?array(1=>1):array()),))."</div>\n";}function
selectLimitPrint($z){echo"<fieldset><legend>".lang(34)."</legend><div>";echo
html_select("limit",array("","50","100"),$z),"</div></fieldset>\n";}function
selectLengthPrint($le){}function
selectActionPrint($w){echo"<fieldset><legend>".lang(35)."</legend><div>","<input type='submit' value='".lang(36)."'>","</div></fieldset>\n";}function
selectCommandPrint(){return
true;}function
selectImportPrint(){return
true;}function
selectEmailPrint($ob,$h){if($ob){print_fieldset("email",lang(37),$_POST["email_append"]);echo"<div onkeydown=\"eventStop(event); return bodyKeydown(event, 'email');\">\n","<p>".lang(38).": <input name='email_from' value='".h($_POST?$_POST["email_from"]:$_COOKIE["adminer_email"])."'>\n",lang(39).": <input name='email_subject' value='".h($_POST["email_subject"])."'>\n","<p><textarea name='email_message' rows='15' cols='75'>".h($_POST["email_message"].($_POST["email_append"]?'{$'."$_POST[email_addition]}":""))."</textarea>\n","<p onkeydown=\"eventStop(event); return bodyKeydown(event, 'email_append');\">".html_select("email_addition",$h,$_POST["email_addition"])."<input type='submit' name='email_append' value='".lang(40)."'>\n";echo"<p>".lang(41).": <input type='file' name='email_files[]' onchange=\"this.onchange = function () { }; var el = this.cloneNode(true); el.value = ''; this.parentNode.appendChild(el);\">","<p>".(count($ob)==1?'<input type="hidden" name="email_field" value="'.h(key($ob)).'">':html_select("email_field",$ob)),"<input type='submit' name='email' value='".lang(42)."' onclick=\"return this.form['delete'].onclick();\">\n","</div>\n","</div></fieldset>\n";}}function
selectColumnsProcess($h,$w){return
array(array(),array());}function
selectSearchProcess($q,$w){$J=array();foreach((array)$_GET["where"]as$y=>$Z){$Ka=$Z["col"];$cd=$Z["op"];$X=$Z["val"];if(($y<0?"":$Ka).$X!=""){$Pa=array();foreach(($Ka!=""?array($Ka=>$q[$Ka]):$q)as$C=>$p){if($Ka!=""||is_numeric($X)||!ereg('int|float|double|decimal',$p["type"])){$C=idf_escape($C);if($Ka!=""&&$p["type"]=="enum")$Pa[]=(in_array(0,$X)?"$C IS NULL OR ":"")."$C IN (".implode(", ",array_map('intval',$X)).")";else{$me=ereg('char|text|enum|set',$p["type"]);$Y=$this->processInput($p,(!$cd&&$me&&ereg('^[^%]+$',$X)?"%$X%":$X));$Pa[]=$C.($Y=="NULL"?" IS".($cd==">="?" NOT":"")." $Y":(in_array($cd,$this->operators)||$cd=="="?" $cd $Y":($me?" LIKE $Y":" IN (".str_replace(",","', '",$Y).")")));if($y<0&&$X=="0")$Pa[]="$C IS NULL";}}}$J[]=($Pa?"(".implode(" OR ",$Pa).")":"0");}}return$J;}function
selectOrderProcess($q,$w){$mc=$_GET["index_order"];if($mc!="")unset($_GET["order"][1]);if($_GET["order"])return
array(idf_escape(reset($_GET["order"])).($_GET["desc"]?" DESC":""));foreach(($mc!=""?array($w[$mc]):$w)as$v){if($mc!=""||$v["type"]=="INDEX"){$bc=array_filter($v["descs"]);$Za=false;foreach($v["columns"]as$X){if(ereg('date|timestamp',$q[$X]["type"])){$Za=true;break;}}$J=array();foreach($v["columns"]as$y=>$X)$J[]=idf_escape($X).(($bc?$v["descs"][$y]:$Za)?" DESC":"");return$J;}}return
array();}function
selectLimitProcess(){return(isset($_GET["limit"])?$_GET["limit"]:"50");}function
selectLengthProcess(){return"100";}function
selectEmailProcess($Z,$Ob){if($_POST["email_append"])return
true;if($_POST["email"]){$Rd=0;if($_POST["all"]||$_POST["check"]){$p=idf_escape($_POST["email_field"]);$de=$_POST["email_subject"];$Pc=$_POST["email_message"];preg_match_all('~\\{\\$([a-z0-9_]+)\\}~i',"$de.$Pc",$Jc);$L=get_rows("SELECT DISTINCT $p".($Jc[1]?", ".implode(", ",array_map('idf_escape',array_unique($Jc[1]))):"")." FROM ".table($_GET["select"])." WHERE $p IS NOT NULL AND $p != ''".($Z?" AND ".implode(" AND ",$Z):"").($_POST["all"]?"":" AND ((".implode(") OR (",array_map('where_check',(array)$_POST["check"]))."))"));$q=fields($_GET["select"]);foreach($this->rowDescriptions($L,$Ob)as$K){$Gd=array('{\\'=>'{');foreach($Jc[1]as$X)$Gd['{$'."$X}"]=$this->editVal($K[$X],$q[$X]);$nb=$K[$_POST["email_field"]];if(is_mail($nb)&&send_mail($nb,strtr($de,$Gd),strtr($Pc,$Gd),$_POST["email_from"],$_FILES["email_files"]))$Rd++;}}cookie("adminer_email",$_POST["email_from"]);redirect(remove_from_uri(),lang(43,$Rd));}return
false;}function
selectQueryBuild($M,$Z,$Xb,$id,$z,$E){return"";}function
messageQuery($H){return" <span class='time'>".@date("H:i:s")."</span><!--\n".str_replace("--","--><!-- ",$H)."\n-->";}function
editFunctions($p){$J=array();if($p["null"]&&ereg('blob',$p["type"]))$J["NULL"]=lang(5);$J[""]=($p["null"]||$p["auto_increment"]||like_bool($p)?"":"*");if(ereg('date|time',$p["type"]))$J["now"]=lang(44);if(eregi('_(md5|sha1)$',$p["field"],$A))$J[]=strtolower($A[1]);return$J;}function
editInput($Q,$p,$sa,$Y){if($p["type"]=="enum")return(isset($_GET["select"])?"<label><input type='radio'$sa value='-1' checked><i>".lang(6)."</i></label> ":"").enum_input("radio",$sa,$p,($Y||isset($_GET["select"])?$Y:0),($p["null"]?"":null));$gd=$this->_foreignKeyOptions($Q,$p["field"],$Y);if($gd!==null)return(is_array($gd)?"<select$sa>".optionlist($gd,$Y,true)."</select>":"<input value='".h($Y)."'$sa class='hidden'><input value='".h($gd)."' class='jsonly' onkeyup=\"whisper('".h(ME."script=complete&source=".urlencode($Q)."&field=".urlencode($p["field"]))."&value=', this);\"><div onclick='return whisperClick(event, this.previousSibling);'></div>");if(like_bool($p))return'<input type="checkbox" value="'.h($Y?$Y:1).'"'.($Y?' checked':'')."$sa>";$ec="";if(ereg('time',$p["type"]))$ec=lang(45);if(ereg('date|timestamp',$p["type"]))$ec=lang(46).($ec?" [$ec]":"");if($ec)return"<input value='".h($Y)."'$sa> ($ec)";if(eregi('_(md5|sha1)$',$p["field"]))return"<input type='password' value='".h($Y)."'$sa>";return'';}function
processInput($p,$Y,$s=""){if($s=="now")return"$s()";$J=$Y;if(ereg('date|timestamp',$p["type"])&&preg_match('(^'.str_replace('\\$1','(?P<p1>\\d*)',preg_replace('~(\\\\\\$([2-6]))~','(?P<p\\2>\\d{1,2})',preg_quote(lang(30)))).'(.*))',$Y,$A))$J=($A["p1"]!=""?$A["p1"]:($A["p2"]!=""?($A["p2"]<70?20:19).$A["p2"]:gmdate("Y")))."-$A[p3]$A[p4]-$A[p5]$A[p6]".end($A);$J=($p["type"]=="bit"&&ereg('^[0-9]+$',$Y)?$J:q($J));if($Y==""&&like_bool($p))$J="0";elseif($Y==""&&($p["null"]||!ereg('char|text',$p["type"])))$J="NULL";elseif(ereg('^(md5|sha1)$',$s))$J="$s($J)";return
unconvert_field($p,$J);}function
dumpOutput(){return
array();}function
dumpFormat(){return
array('csv'=>'CSV,','csv;'=>'CSV;','tsv'=>'TSV');}function
dumpDatabase($n){}function
dumpTable(){echo"\xef\xbb\xbf";}function
dumpData($Q,$ce,$H){global$j;$I=$j->query($H,1);if($I){while($K=$I->fetch_assoc()){if($ce=="table"){dump_csv(array_keys($K));$ce="INSERT";}dump_csv($K);}}}function
dumpFilename($ic){return
friendly_url($ic);}function
dumpHeaders($ic,$Tc=false){$Ab="csv";header("Content-Type: text/csv; charset=utf-8");return$Ab;}function
homepage(){return
true;}function
navigation($Sc){global$ba,$ue;echo'<h1>
',$this->name(),' <span class="version">',$ba,'</span>
<a href="http://www.adminer.org/editor/#download" id="version">',(version_compare($ba,$_COOKIE["adminer_version"])<0?h($_COOKIE["adminer_version"]):""),'</a>
</h1>
';if($Sc=="auth"){$Kb=true;foreach((array)$_SESSION["pwds"]["server"][""]as$V=>$F){if($F!==null){if($Kb){echo"<p id='logins' onmouseover='menuOver(this, event);' onmouseout='menuOut(this);'>\n";$Kb=false;}echo"<a href='".h(auth_url("server","",$V))."'>".($V!=""?h($V):"<i>".lang(5)."</i>")."</a><br>\n";}}}else{echo'<form action="" method="post">
<p class="logout">
<input type="submit" name="logout" value="',lang(47),'" id="logout">
<input type="hidden" name="token" value="',$ue,'">
</p>
</form>
';$this->databasesPrint($Sc);if($Sc!="db"&&$Sc!="ns"){$R=table_status('',true);if(!$R)echo"<p class='message'>".lang(7)."\n";else$this->tablesPrint($R);}}}function
databasesPrint($Sc){}function
tablesPrint($S){echo"<p id='tables' onmouseover='menuOver(this, event);' onmouseout='menuOut(this);'>\n";foreach($S
as$K){$C=$this->tableName($K);if(isset($K["Engine"])&&$C!="")echo"<a href='".h(ME).'select='.urlencode($K["Name"])."'".bold($_GET["select"]==$K["Name"]||$_GET["edit"]==$K["Name"])." title='".lang(48)."'>$C</a><br>\n";}}function
_foreignColumn($Ob,$g){foreach((array)$Ob[$g]as$Nb){if(count($Nb["source"])==1){$C=$this->rowDescription($Nb["table"]);if($C!=""){$hc=idf_escape($Nb["target"][0]);return
array($Nb["table"],$hc,$C);}}}}function
_foreignKeyOptions($Q,$g,$Y=null){global$j;if(list($T,$hc,$C)=$this->_foreignColumn(column_foreign_keys($Q),$g)){$J=&$this->_values[$T];if($J===null){$R=table_status($T);$J=($R["Rows"]>1000?"":array(""=>"")+get_key_vals("SELECT $hc, $C FROM ".table($T)." ORDER BY 2"));}if(!$J&&$Y!==null)return$j->result("SELECT $C FROM ".table($T)." WHERE $hc = ".q($Y));return$J;}}}$c=(function_exists('adminer_object')?adminer_object():new
Adminer);function
page_header($pe,$o="",$Da=array(),$qe=""){global$a,$c,$j,$gb;header("Content-Type: text/html; charset=utf-8");if($c->headers()){header("X-Frame-Options: deny");header("X-XSS-Protection: 0");}$re=$pe.($qe!=""?": ".h($qe):"");$se=strip_tags($re.(SERVER!=""&&SERVER!="localhost"?h(" - ".SERVER):"")." - ".$c->name());echo'<!DOCTYPE html>
<html lang="',$a,'" dir="',lang(49),'">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta name="robots" content="noindex">
<title>',$se,'</title>
<link rel="stylesheet" type="text/css" href="',h(preg_replace("~\\?.*~","",ME))."?file=default.css&amp;version=3.7.1",'">
<script type="text/javascript" src="',h(preg_replace("~\\?.*~","",ME))."?file=functions.js&amp;version=3.7.1",'"></script>
';if($c->head()){echo'<link rel="shortcut icon" type="image/x-icon" href="',h(preg_replace("~\\?.*~","",ME))."?file=favicon.ico&amp;version=3.7.1",'">
<link rel="apple-touch-icon" href="',h(preg_replace("~\\?.*~","",ME))."?file=favicon.ico&amp;version=3.7.1",'">
';if(file_exists("adminer.css")){echo'<link rel="stylesheet" type="text/css" href="adminer.css">
';}}echo'
<body class="',lang(49),' nojs" onkeydown="bodyKeydown(event);" onclick="bodyClick(event);" onload="bodyLoad(\'',(is_object($j)?substr($j->server_info,0,3):""),'\');',(isset($_COOKIE["adminer_version"])?"":" verifyVersion();"),'">
<script type="text/javascript">
document.body.className = document.body.className.replace(/ nojs/, \' js\');
</script>

<div id="content">
';if($Da!==null){$_=substr(preg_replace('~(username|db|ns)=[^&]*&~','',ME),0,-1);echo'<p id="breadcrumb"><a href="'.h($_?$_:".").'">'.$gb[DRIVER].'</a> &raquo; ';$_=substr(preg_replace('~(db|ns)=[^&]*&~','',ME),0,-1);$N=(SERVER!=""?h(SERVER):lang(50));if($Da===false)echo"$N\n";else{echo"<a href='".($_?h($_):".")."' accesskey='1' title='Alt+Shift+1'>$N</a> &raquo; ";if($_GET["ns"]!=""||(DB!=""&&is_array($Da)))echo'<a href="'.h($_."&db=".urlencode(DB).(support("scheme")?"&ns=":"")).'">'.h(DB).'</a> &raquo; ';if(is_array($Da)){if($_GET["ns"]!="")echo'<a href="'.h(substr(ME,0,-1)).'">'.h($_GET["ns"]).'</a> &raquo; ';foreach($Da
as$y=>$X){$Za=(is_array($X)?$X[1]:$X);if($Za!="")echo'<a href="'.h(ME."$y=").urlencode(is_array($X)?$X[0]:$X).'">'.h($Za).'</a> &raquo; ';}}echo"$pe\n";}}echo"<h2>$re</h2>\n";restart_session();$Ke=preg_replace('~^[^?]*~','',$_SERVER["REQUEST_URI"]);$Qc=$_SESSION["messages"][$Ke];if($Qc){echo"<div class='message'>".implode("</div>\n<div class='message'>",$Qc)."</div>\n";unset($_SESSION["messages"][$Ke]);}$m=&get_session_adminer("dbs");if(DB!=""&&$m&&!in_array(DB,$m,true))$m=null;stop_session();if($o)echo"<div class='error'>$o</div>\n";define("PAGE_HEADER",1);}function
page_footer($Sc=""){global$c;echo'</div>

';switch_lang();echo'<div id="menu">
';$c->navigation($Sc);echo'</div>
<script type="text/javascript">setupSubmitHighlight(document);</script>
';}function
int32($B){while($B>=2147483648)$B-=4294967296;while($B<=-2147483649)$B+=4294967296;return(int)$B;}function
long2str($W,$Se){$Md='';foreach($W
as$X)$Md.=pack('V',$X);if($Se)return
substr($Md,0,end($W));return$Md;}function
str2long($Md,$Se){$W=array_values(unpack('V*',str_pad($Md,4*ceil(strlen($Md)/4),"\0")));if($Se)$W[]=strlen($Md);return$W;}function
xxtea_mx($Xe,$We,$fe,$uc){return
int32((($Xe>>5&0x7FFFFFF)^$We<<2)+(($We>>3&0x1FFFFFFF)^$Xe<<4))^int32(($fe^$We)+($uc^$Xe));}function
encrypt_string($ae,$y){if($ae=="")return"";$y=array_values(unpack("V*",pack("H*",md5($y))));$W=str2long($ae,true);$B=count($W)-1;$Xe=$W[$B];$We=$W[0];$zd=floor(6+52/($B+1));$fe=0;while($zd-->0){$fe=int32($fe+0x9E3779B9);$jb=$fe>>2&3;for($md=0;$md<$B;$md++){$We=$W[$md+1];$Uc=xxtea_mx($Xe,$We,$fe,$y[$md&3^$jb]);$Xe=int32($W[$md]+$Uc);$W[$md]=$Xe;}$We=$W[0];$Uc=xxtea_mx($Xe,$We,$fe,$y[$md&3^$jb]);$Xe=int32($W[$B]+$Uc);$W[$B]=$Xe;}return
long2str($W,false);}function
decrypt_string($ae,$y){if($ae=="")return"";if(!$y)return
false;$y=array_values(unpack("V*",pack("H*",md5($y))));$W=str2long($ae,false);$B=count($W)-1;$Xe=$W[$B];$We=$W[0];$zd=floor(6+52/($B+1));$fe=int32($zd*0x9E3779B9);while($fe){$jb=$fe>>2&3;for($md=$B;$md>0;$md--){$Xe=$W[$md-1];$Uc=xxtea_mx($Xe,$We,$fe,$y[$md&3^$jb]);$We=int32($W[$md]-$Uc);$W[$md]=$We;}$Xe=$W[$B];$Uc=xxtea_mx($Xe,$We,$fe,$y[$md&3^$jb]);$We=int32($W[0]-$Uc);$W[0]=$We;$fe=int32($fe-0x9E3779B9);}return
long2str($W,true);}$j='';$ue=$_SESSION["token"];if(!$_SESSION["token"])$_SESSION["token"]=rand(1,1e6);$qd=array();if($_COOKIE["adminer_permanent"]){foreach(explode(" ",$_COOKIE["adminer_permanent"])as$X){list($y)=explode(":",$X);$qd[$y]=$X;}}$e=$_POST["auth"];if($e){session_regenerate_id();$_SESSION["pwds"][$e["driver"]][$e["server"]][$e["username"]]=$e["password"];$_SESSION["db"][$e["driver"]][$e["server"]][$e["username"]][$e["db"]]=true;if($e["permanent"]){$y=base64_encode($e["driver"])."-".base64_encode($e["server"])."-".base64_encode($e["username"])."-".base64_encode($e["db"]);$wd=$c->permanentLogin(true);$qd[$y]="$y:".base64_encode($wd?encrypt_string($e["password"],$wd):"");cookie("adminer_permanent",implode(" ",$qd));}if(count($_POST)==1||DRIVER!=$e["driver"]||SERVER!=$e["server"]||$_GET["username"]!==$e["username"]||DB!=$e["db"])redirect(auth_url($e["driver"],$e["server"],$e["username"],$e["db"]));}elseif($_POST["logout"]){if($ue&&$_POST["token"]!=$ue){page_header(lang(47),lang(51));page_footer("db");exit;}else{foreach(array("pwds","db","dbs","queries")as$y)set_session($y,null);unset_permanent();redirect(substr(preg_replace('~(username|db|ns)=[^&]*&~','',ME),0,-1),lang(52));}}elseif($qd&&!$_SESSION["pwds"]){session_regenerate_id();$wd=$c->permanentLogin();foreach($qd
as$y=>$X){list(,$Ga)=explode(":",$X);list($fb,$N,$V,$n)=array_map('base64_decode',explode("-",$y));$_SESSION["pwds"][$fb][$N][$V]=decrypt_string(base64_decode($Ga),$wd);$_SESSION["db"][$fb][$N][$V][$n]=true;}}function
unset_permanent(){global$qd;foreach($qd
as$y=>$X){list($fb,$N,$V,$n)=array_map('base64_decode',explode("-",$y));if($fb==DRIVER&&$N==SERVER&&$V==$_GET["username"]&&$n==DB)unset($qd[$y]);}cookie("adminer_permanent",implode(" ",$qd));}function
auth_error($yb=null){global$j,$c,$ue;$Ud=session_name();$o="";if(!$_COOKIE[$Ud]&&$_GET[$Ud]&&ini_bool("session.use_only_cookies"))$o=lang(53);elseif(isset($_GET["username"])){if(($_COOKIE[$Ud]||$_GET[$Ud])&&!$ue)$o=lang(54);else{$F=&get_session_adminer("pwds");if($F!==null){$o=h($yb?$yb->getMessage():(is_string($j)?$j:lang(55)));if($F===false)$o.='<br>'.lang(56,'<code>permanentLogin()</code>');$F=null;}unset_permanent();}}page_header(lang(24),$o,null);echo"<form action='' method='post'>\n";$c->loginForm();echo"<div>";hidden_fields($_POST,array("auth"));echo"</div>\n","</form>\n";page_footer("auth");}if(isset($_GET["username"])){if(!class_exists("Min_DB")){unset($_SESSION["pwds"][DRIVER]);unset_permanent();page_header(lang(57),lang(58,implode(", ",$td)),false);page_footer("auth");exit;}$j=connect();}if(is_string($j)||!$c->login($_GET["username"],get_session_adminer("pwds"))){auth_error();exit;}$ue=$_SESSION["token"];if($e&&$_POST["token"])$_POST["token"]=$ue;$o='';if($_POST){if($_POST["token"]!=$ue){$qc="max_input_vars";$Nc=ini_get($qc);if(extension_loaded("suhosin")){foreach(array("suhosin.request.max_vars","suhosin.post.max_vars")as$y){$X=ini_get($y);if($X&&(!$Nc||$X<$Nc)){$qc=$y;$Nc=$X;}}}$o=(!$_POST["token"]&&$Nc?lang(59,"'$qc'"):lang(51));}}elseif($_SERVER["REQUEST_METHOD"]=="POST"){$o=lang(60,"'post_max_size'");if(isset($_GET["sql"]))$o.=' '.lang(61);}if(!ini_bool("session.use_cookies")||@ini_set("session.use_cookies",false)!==false){session_cache_limiter("");session_write_close();}$j->select_db($c->database());function
email_header($cc){return"=?UTF-8?B?".base64_encode($cc)."?=";}function
send_mail($nb,$de,$Pc,$Ub="",$Ib=array()){$ub=(strncasecmp(PHP_OS,"win",3)?"\n":"\r\n");$Pc=str_replace("\n",$ub,wordwrap(str_replace("\r","","$Pc\n")));$Ca=uniqid("boundary");$ra="";foreach((array)$Ib["error"]as$y=>$X){if(!$X)$ra.="--$Ca$ub"."Content-Type: ".str_replace("\n","",$Ib["type"][$y]).$ub."Content-Disposition: attachment; filename=\"".preg_replace('~["\\n]~','',$Ib["name"][$y])."\"$ub"."Content-Transfer-Encoding: base64$ub$ub".chunk_split(base64_encode(file_get_contents($Ib["tmp_name"][$y])),76,$ub).$ub;}$za="";$dc="Content-Type: text/plain; charset=utf-8$ub"."Content-Transfer-Encoding: 8bit";if($ra){$ra.="--$Ca--$ub";$za="--$Ca$ub$dc$ub$ub";$dc="Content-Type: multipart/mixed; boundary=\"$Ca\"";}$dc.=$ub."MIME-Version: 1.0$ub"."X-Mailer: Adminer Editor".($Ub?$ub."From: ".str_replace("\n","",$Ub):"");return
mail($nb,email_header($de),$za.$Pc.$ra,$dc);}function
like_bool($p){return
ereg("bool|(tinyint|bit)\\(1\\)",$p["full_type"]);}$Zc="RESTRICT|NO ACTION|CASCADE|SET NULL|SET DEFAULT";$gb[DRIVER]=lang(24);if(isset($_GET["select"])&&($_POST["edit"]||$_POST["clone"])&&!$_POST["save"])$_GET["edit"]=$_GET["select"];if(isset($_GET["download"])){$b=$_GET["download"];$q=fields($b);header("Content-Type: application/octet-stream");header("Content-Disposition: attachment; filename=".friendly_url("$b-".implode("_",$_GET["where"])).".".friendly_url($_GET["field"]));echo$j->result("SELECT".limit(idf_escape($_GET["field"])." FROM ".table($b)," WHERE ".where($_GET,$q),1));exit;}elseif(isset($_GET["edit"])){$b=$_GET["edit"];$q=fields($b);$Z=(isset($_GET["select"])?(count($_POST["check"])==1?where_check($_POST["check"][0],$q):""):where($_GET,$q));$Je=(isset($_GET["select"])?$_POST["edit"]:$Z);foreach($q
as$C=>$p){if(!isset($p["privileges"][$Je?"update":"insert"])||$c->fieldName($p)=="")unset($q[$C]);}if($_POST&&!$o&&!isset($_GET["select"])){$Fc=$_POST["referer"];if($_POST["insert"])$Fc=($Je?null:$_SERVER["REQUEST_URI"]);elseif(!ereg('^.+&select=.+$',$Fc))$Fc=ME."select=".urlencode($b);$w=indexes($b);$Ee=unique_array($_GET["where"],$w);$Ad="\nWHERE $Z";if(isset($_POST["delete"])){$H="FROM ".table($b);query_redirect("DELETE".($Ee?" $H$Ad":limit1($H,$Ad)),$Fc,lang(62));}else{$O=array();foreach($q
as$C=>$p){$X=process_input($p);if($X!==false&&$X!==null)$O[idf_escape($C)]=($Je?"\n".idf_escape($C)." = $X":$X);}if($Je){if(!$O)redirect($Fc);$H=table($b)." SET".implode(",",$O);query_redirect("UPDATE".($Ee?" $H$Ad":limit1($H,$Ad)),$Fc,lang(63));}else{$I=insert_into($b,$O);$Bc=($I?last_id():0);queries_redirect($Fc,lang(64,($Bc?" $Bc":"")),$I);}}}$ie=$c->tableName(table_status1($b,true));page_header(($Je?lang(65):lang(40)),$o,array("select"=>array($b,$ie)),$ie);$K=null;if($_POST["save"])$K=(array)$_POST["fields"];elseif($Z){$M=array();foreach($q
as$C=>$p){if(isset($p["privileges"]["select"])){$pa=convert_field($p);if($_POST["clone"]&&$p["auto_increment"])$pa="''";if($x=="sql"&&ereg("enum|set",$p["type"]))$pa="1*".idf_escape($C);$M[]=($pa?"$pa AS ":"").idf_escape($C);}}$K=array();if($M){$L=get_rows("SELECT".limit(implode(", ",$M)." FROM ".table($b)," WHERE $Z",(isset($_GET["select"])?2:1)));$K=(isset($_GET["select"])&&count($L)!=1?null:reset($L));}}if($K===false)echo"<p class='error'>".lang(66)."\n";echo'
<form action="" method="post" enctype="multipart/form-data" id="form">
';if(!$q)echo"<p class='error'>".lang(67)."\n";else{echo"<table cellspacing='0' onkeydown='return editingKeydown(event);'>\n";foreach($q
as$C=>$p){echo"<tr><th>".$c->fieldName($p);$Ya=$_GET["set"][bracket_escape($C)];if($Ya===null){$Ya=$p["default"];if($p["type"]=="bit"&&ereg("^b'([01]*)'\$",$Ya,$Dd))$Ya=$Dd[1];}$Y=($K!==null?($K[$C]!=""&&$x=="sql"&&ereg("enum|set",$p["type"])?(is_array($K[$C])?array_sum($K[$C]):+$K[$C]):$K[$C]):(!$Je&&$p["auto_increment"]?"":(isset($_GET["select"])?false:$Ya)));if(!$_POST["save"]&&is_string($Y))$Y=$c->editVal($Y,$p);$s=($_POST["save"]?(string)$_POST["function"][$C]:($Je&&$p["on_update"]=="CURRENT_TIMESTAMP"?"now":($Y===false?null:($Y!==null?'':'NULL'))));if(ereg("time",$p["type"])&&$Y=="CURRENT_TIMESTAMP"){$Y="";$s="now";}input($p,$Y,$s);echo"\n";}echo"</table>\n";}echo'<p>
';if($q){echo"<input type='submit' value='".lang(68)."'>\n";if(!isset($_GET["select"]))echo"<input type='submit' name='insert' value='".($Je?lang(69):lang(70))."' title='Ctrl+Shift+Enter'>\n";}echo($Je?"<input type='submit' name='delete' value='".lang(71)."' onclick=\"return confirm('".lang(0)."');\">\n":($_POST||!$q?"":"<script type='text/javascript'>focus(document.getElementById('form').getElementsByTagName('td')[1].firstChild);</script>\n"));if(isset($_GET["select"]))hidden_fields(array("check"=>(array)$_POST["check"],"clone"=>$_POST["clone"],"all"=>$_POST["all"]));echo'<input type="hidden" name="referer" value="',h(isset($_POST["referer"])?$_POST["referer"]:$_SERVER["HTTP_REFERER"]),'">
<input type="hidden" name="save" value="1">
<input type="hidden" name="token" value="',$ue,'">
</form>
';}elseif(isset($_GET["select"])){$b=$_GET["select"];$R=table_status1($b);$w=indexes($b);$q=fields($b);$Pb=column_foreign_keys($b);$Yc="";if($R["Oid"]){$Yc=($x=="sqlite"?"rowid":"oid");$w[]=array("type"=>"PRIMARY","columns"=>array($Yc));}parse_str($_COOKIE["adminer_import"],$ka);$Kd=array();$h=array();$le=null;foreach($q
as$y=>$p){$C=$c->fieldName($p);if(isset($p["privileges"]["select"])&&$C!=""){$h[$y]=html_entity_decode(strip_tags($C),ENT_QUOTES);if(is_shortable($p))$le=$c->selectLengthProcess();}$Kd+=$p["privileges"];}list($M,$Xb)=$c->selectColumnsProcess($h,$w);$tc=count($Xb)<count($M);$Z=$c->selectSearchProcess($q,$w);$id=$c->selectOrderProcess($q,$w);$z=$c->selectLimitProcess();$Ub=($M?implode(", ",$M):"*".($Yc?", $Yc":"")).convert_fields($h,$q,$M)."\nFROM ".table($b);$Yb=($Xb&&$tc?"\nGROUP BY ".implode(", ",$Xb):"").($id?"\nORDER BY ".implode(", ",$id):"");if($_GET["val"]&&is_ajax()){header("Content-Type: text/plain; charset=utf-8");foreach($_GET["val"]as$Fe=>$K){$pa=convert_field($q[key($K)]);echo$j->result("SELECT".limit($pa?$pa:idf_escape(key($K))." FROM ".table($b)," WHERE ".where_check($Fe,$q).($Z?" AND ".implode(" AND ",$Z):"").($id?" ORDER BY ".implode(", ",$id):""),1));}exit;}if($_POST&&!$o){$Ue=$Z;if(is_array($_POST["check"]))$Ue[]="((".implode(") OR (",array_map('where_check',$_POST["check"]))."))";$Ue=($Ue?"\nWHERE ".implode(" AND ",$Ue):"");$ud=$He=null;foreach($w
as$v){if($v["type"]=="PRIMARY"){$ud=array_flip($v["columns"]);$He=($M?$ud:array());break;}}foreach((array)$He
as$y=>$X){if(in_array(idf_escape($y),$M))unset($He[$y]);}if($_POST["export"]){cookie("adminer_import","output=".urlencode($_POST["output"])."&format=".urlencode($_POST["format"]));dump_headers($b);$c->dumpTable($b,"");if(!is_array($_POST["check"])||$He===array())$H="SELECT $Ub$Ue$Yb";else{$De=array();foreach($_POST["check"]as$X)$De[]="(SELECT".limit($Ub,"\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$q).$Yb,1).")";$H=implode(" UNION ALL ",$De);}$c->dumpData($b,"table",$H);exit;}if(!$c->selectEmailProcess($Z,$Pb)){if($_POST["save"]||$_POST["delete"]){$I=true;$la=0;$H=table($b);$O=array();if(!$_POST["delete"]){foreach($h
as$C=>$X){$X=process_input($q[$C]);if($X!==null){if($_POST["clone"])$O[idf_escape($C)]=($X!==false?$X:idf_escape($C));elseif($X!==false)$O[]=idf_escape($C)." = $X";}}$H.=($_POST["clone"]?" (".implode(", ",array_keys($O)).")\nSELECT ".implode(", ",$O)."\nFROM ".table($b):" SET\n".implode(",\n",$O));}if($_POST["delete"]||$O){$Na="UPDATE";if($_POST["delete"]){$Na="DELETE";$H="FROM $H";}if($_POST["clone"]){$Na="INSERT";$H="INTO $H";}if($_POST["all"]||($He===array()&&is_array($_POST["check"]))||$tc){$I=queries("$Na $H$Ue");$la=$j->affected_rows;}else{foreach((array)$_POST["check"]as$X){$I=queries($Na.limit1($H,"\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$q)));if(!$I)break;$la+=$j->affected_rows;}}}$Pc=lang(72,$la);if($_POST["clone"]&&$I&&$la==1){$Bc=last_id();if($Bc)$Pc=lang(64," $Bc");}queries_redirect(remove_from_uri($_POST["all"]&&$_POST["delete"]?"page":""),$Pc,$I);}elseif(!$_POST["import"]){if(!$_POST["val"])$o=lang(73);else{$I=true;$la=0;foreach($_POST["val"]as$Fe=>$K){$O=array();foreach($K
as$y=>$X){$y=bracket_escape($y,1);$O[]=idf_escape($y)." = ".(ereg('char|text',$q[$y]["type"])||$X!=""?$c->processInput($q[$y],$X):"NULL");}$H=table($b)." SET ".implode(", ",$O);$Te=" WHERE ".where_check($Fe,$q).($Z?" AND ".implode(" AND ",$Z):"");$I=queries("UPDATE".($tc||$He===array()?" $H$Te":limit1($H,$Te)));if(!$I)break;$la+=$j->affected_rows;}queries_redirect(remove_from_uri(),lang(72,$la),$I);}}elseif(!is_string($Gb=get_file("csv_file",true)))$o=upload_error($Gb);elseif(!preg_match('~~u',$Gb))$o=lang(74);else{cookie("adminer_import","output=".urlencode($ka["output"])."&format=".urlencode($_POST["separator"]));$I=true;$Ma=array_keys($q);preg_match_all('~(?>"[^"]*"|[^"\\r\\n]+)+~',$Gb,$Jc);$la=count($Jc[0]);begin();$Sd=($_POST["separator"]=="csv"?",":($_POST["separator"]=="tsv"?"\t":";"));foreach($Jc[0]as$y=>$X){preg_match_all("~((?>\"[^\"]*\")+|[^$Sd]*)$Sd~",$X.$Sd,$Kc);if(!$y&&!array_diff($Kc[1],$Ma)){$Ma=$Kc[1];$la--;}else{$O=array();foreach($Kc[1]as$t=>$Ka)$O[idf_escape($Ma[$t])]=($Ka==""&&$q[$Ma[$t]]["null"]?"NULL":q(str_replace('""','"',preg_replace('~^"|"$~','',$Ka))));$I=insert_update($b,$O,$ud);if(!$I)break;}}if($I)queries("COMMIT");queries_redirect(remove_from_uri("page"),lang(75,$la),$I);queries("ROLLBACK");}}}$ie=$c->tableName($R);if(is_ajax())ob_start();page_header(lang(36).": $ie",$o);$O=null;if(isset($Kd["insert"])){$O="";foreach((array)$_GET["where"]as$X){if(count($Pb[$X["col"]])==1&&($X["op"]=="="||(!$X["op"]&&!ereg('[_%]',$X["val"]))))$O.="&set".urlencode("[".bracket_escape($X["col"])."]")."=".urlencode($X["val"]);}}$c->selectLinks($R,$O);if(!$h)echo"<p class='error'>".lang(76).($q?".":": ".error())."\n";else{echo"<form action='' id='form'>\n","<div style='display: none;'>";hidden_fields_get();echo(DB!=""?'<input type="hidden" name="db" value="'.h(DB).'">'.(isset($_GET["ns"])?'<input type="hidden" name="ns" value="'.h($_GET["ns"]).'">':""):"");echo'<input type="hidden" name="select" value="'.h($b).'">',"</div>\n";$c->selectColumnsPrint($M,$h);$c->selectSearchPrint($Z,$h,$w);$c->selectOrderPrint($id,$h,$w);$c->selectLimitPrint($z);$c->selectLengthPrint($le);$c->selectActionPrint($w);echo"</form>\n";$E=$_GET["page"];if($E=="last"){$Sb=$j->result("SELECT COUNT(*) FROM ".table($b).($Z?" WHERE ".implode(" AND ",$Z):""));$E=floor(max(0,$Sb-1)/$z);}$H=$c->selectQueryBuild($M,$Z,$Xb,$id,$z,$E);if(!$H)$H="SELECT".limit((+$z&&$Xb&&$tc&&$x=="sql"?"SQL_CALC_FOUND_ROWS ":"").$Ub,($Z?"\nWHERE ".implode(" AND ",$Z):"").$Yb,($z!=""?+$z:null),($E?$z*$E:0),"\n");echo$c->selectQuery($H);$I=$j->query($H);if(!$I)echo"<p class='error'>".error()."\n";else{if($x=="mssql"&&$E)$I->seek($z*$E);$pb=array();echo"<form action='' method='post' enctype='multipart/form-data'>\n";$L=array();while($K=$I->fetch_assoc()){if($E&&$x=="oracle")unset($K["RNUM"]);$L[]=$K;}if($_GET["page"]!="last")$Sb=(+$z&&$Xb&&$tc?($x=="sql"?$j->result(" SELECT FOUND_ROWS()"):$j->result("SELECT COUNT(*) FROM ($H) x")):count($L));if(!$L)echo"<p class='message'>".lang(66)."\n";else{$ya=$c->backwardKeys($b,$ie);echo"<table id='table' cellspacing='0' class='nowrap checkable' onclick='tableClick(event);' ondblclick='tableClick(event, true);' onkeydown='return editingKeydown(event);'>\n","<thead><tr>".(!$Xb&&$M?"":"<td><input type='checkbox' id='all-page' onclick='formCheck(this, /check/);'> <a href='".h($_GET["modify"]?remove_from_uri("modify"):$_SERVER["REQUEST_URI"]."&modify=1")."'>".lang(77)."</a>");$Vc=array();$Wb=array();reset($M);$Bd=1;foreach($L[0]as$y=>$X){if($y!=$Yc){$X=$_GET["columns"][key($M)];$p=$q[$M?($X?$X["col"]:current($M)):$y];$C=($p?$c->fieldName($p,$Bd):"*");if($C!=""){$Bd++;$Vc[$y]=$C;$g=idf_escape($y);$gc=remove_from_uri('(order|desc)[^=]*|page').'&order%5B0%5D='.urlencode($y);$Za="&desc%5B0%5D=1";echo'<th onmouseover="columnMouse(this);" onmouseout="columnMouse(this, \' hidden\');">','<a href="'.h($gc.($id[0]==$g||$id[0]==$y||(!$id&&$tc&&$Xb[0]==$g)?$Za:'')).'">';echo(!$M||$X?apply_sql_function($X["fun"],$C):h(current($M)))."</a>";echo"<span class='column hidden'>","<a href='".h($gc.$Za)."' title='".lang(78)."' class='text'> â†“</a>";if(!$X["fun"])echo'<a href="#fieldset-search" onclick="selectSearch(\''.h(js_escape($y)).'\'); return false;" title="'.lang(31).'" class="text jsonly"> =</a>';echo"</span>";}$Wb[$y]=$X["fun"];next($M);}}$Ec=array();if($_GET["modify"]){foreach($L
as$K){foreach($K
as$y=>$X)$Ec[$y]=max($Ec[$y],min(40,strlen(utf8_decode($X))));}}echo($ya?"<th>".lang(79):"")."</thead>\n";if(is_ajax()){if($z%2==1&&$E%2==1)odd();ob_end_clean();}foreach($c->rowDescriptions($L,$Pb)as$B=>$K){$Ee=unique_array($L[$B],$w);if(!$Ee){$Ee=array();foreach($L[$B]as$y=>$X){if(!preg_match('~^(COUNT\\((\\*|(DISTINCT )?`(?:[^`]|``)+`)\\)|(AVG|GROUP_CONCAT|MAX|MIN|SUM)\\(`(?:[^`]|``)+`\\))$~',$y))$Ee[$y]=$X;}}$Fe="";foreach($Ee
as$y=>$X){if(strlen($X)>64){$y="MD5(".(strpos($y,'(')?$y:idf_escape($y)).")";$X=md5($X);}$Fe.="&".($X!==null?urlencode("where[".bracket_escape($y)."]")."=".urlencode($X):"null%5B%5D=".urlencode($y));}echo"<tr".odd().">".(!$Xb&&$M?"":"<td>".checkbox("check[]",substr($Fe,1),in_array(substr($Fe,1),(array)$_POST["check"]),"","this.form['all'].checked = false; formUncheck('all-page');").($tc||information_schema(DB)?"":" <a href='".h(ME."edit=".urlencode($b).$Fe)."'>".lang(77)."</a>"));foreach($K
as$y=>$X){if(isset($Vc[$y])){$p=$q[$y];if($X!=""&&(!isset($pb[$y])||$pb[$y]!=""))$pb[$y]=(is_mail($X)?$Vc[$y]:"");$_="";$X=$c->editVal($X,$p);if($X!==null){if(ereg('blob|bytea|raw|file',$p["type"])&&$X!="")$_=ME.'download='.urlencode($b).'&field='.urlencode($y).$Fe;if($X==="")$X="&nbsp;";elseif($le!=""&&is_shortable($p))$X=shorten_utf8($X,max(0,+$le));else$X=h($X);if(!$_){foreach((array)$Pb[$y]as$r){if(count($Pb[$y])==1||end($r["source"])==$y){$_="";foreach($r["source"]as$t=>$Vd)$_.=where_link($t,$r["target"][$t],$L[$B][$Vd]);$_=($r["db"]!=""?preg_replace('~([?&]db=)[^&]+~','\\1'.urlencode($r["db"]),ME):ME).'select='.urlencode($r["table"]).$_;if(count($r["source"])==1)break;}}}if($y=="COUNT(*)"){$_=ME."select=".urlencode($b);$t=0;foreach((array)$_GET["where"]as$W){if(!array_key_exists($W["col"],$Ee))$_.=where_link($t++,$W["col"],$W["val"],$W["op"]);}foreach($Ee
as$uc=>$W)$_.=where_link($t++,$uc,$W);}}if(!$_&&($_=$c->selectLink($K[$y],$p))===null){if(is_mail($K[$y]))$_="mailto:$K[$y]";if($yd=is_url($K[$y]))$_=($yd=="http"&&$aa?$K[$y]:"$yd://www.adminer.org/redirect/?url=".urlencode($K[$y]));}$hc=h("val[$Fe][".bracket_escape($y)."]");$Y=$_POST["val"][$Fe][bracket_escape($y)];$ac=h($Y!==null?$Y:$K[$y]);$Hc=strpos($X,"<i>...</i>");$lb=is_utf8($X)&&$L[$B][$y]==$K[$y]&&!$Wb[$y];$ke=ereg('text|lob',$p["type"]);echo(($_GET["modify"]&&$lb)||$Y!==null?"<td>".($ke?"<textarea name='$hc' cols='30' rows='".(substr_count($K[$y],"\n")+1)."'>$ac</textarea>":"<input name='$hc' value='$ac' size='$Ec[$y]'>"):"<td id='$hc' onclick=\"selectClick(this, event, ".($Hc?2:($ke?1:0)).($lb?"":", '".h(lang(80))."'").");\">".$c->selectVal($X,$_,$p));}}if($ya)echo"<td>";$c->backwardKeysPrint($ya,$L[$B]);echo"</tr>\n";}if(is_ajax())exit;echo"</table>\n",(!$Xb&&$M?"":"<script type='text/javascript'>tableCheck();</script>\n");}if(($L||$E)&&!is_ajax()){$xb=true;if($_GET["page"]!="last"&&+$z&&!$tc&&($Sb>=$z||$E)){$Sb=found_rows($R,$Z);if($Sb<max(1e4,2*($E+1)*$z))$Sb=reset(slow_query("SELECT COUNT(*) FROM ".table($b).($Z?" WHERE ".implode(" AND ",$Z):"")));else$xb=false;}if(+$z&&($Sb===false||$Sb>$z||$E)){echo"<p class='pages'>";$Lc=($Sb===false?$E+(count($L)>=$z?2:1):floor(($Sb-1)/$z));echo'<a href="'.h(remove_from_uri("page"))."\" onclick=\"pageClick(this.href, +prompt('".lang(81)."', '".($E+1)."'), event); return false;\">".lang(81)."</a>:",pagination(0,$E).($E>5?" ...":"");for($t=max(1,$E-4);$t<min($Lc,$E+5);$t++)echo
pagination($t,$E);if($Lc>0){echo($E+5<$Lc?" ...":""),($xb&&$Sb!==false?pagination($Lc,$E):" <a href='".h(remove_from_uri("page")."&page=last")."' title='~$Lc'>".lang(82)."</a>");}echo(($Sb===false?count($L)+1:$Sb-$E*$z)>$z?' <a href="'.h(remove_from_uri("page")."&page=".($E+1)).'" onclick="return !selectLoadMore(this, '.(+$z).', \''.lang(83).'\');">'.lang(84).'</a>':'');}echo"<p>\n",($Sb!==false?"(".($xb?"":"~ ").lang(85,$Sb).") ":""),checkbox("all",1,0,lang(86))."\n";if($c->selectCommandPrint()){echo'<fieldset><legend>',lang(65),'</legend><div>
<input type="submit" value="',lang(68),'"',($_GET["modify"]?'':' title="'.lang(73).'" class="jsonly"'),'>
<input type="submit" name="edit" value="',lang(65),'">
<input type="submit" name="clone" value="',lang(87),'">
<input type="submit" name="delete" value="',lang(71),'" onclick="return confirm(\'',lang(0);?> (' + (this.form['all'].checked ? <?php echo$Sb,' : formChecked(this, /check/)) + \')\');">
</div></fieldset>
';}$Qb=$c->dumpFormat();foreach((array)$_GET["columns"]as$g){if($g["fun"]){unset($Qb['sql']);break;}}if($Qb){print_fieldset("export",lang(88));$ld=$c->dumpOutput();echo($ld?html_select("output",$ld,$ka["output"])." ":""),html_select("format",$Qb,$ka["format"])," <input type='submit' name='export' value='".lang(88)."'>\n","</div></fieldset>\n";}}if($c->selectImportPrint()){print_fieldset("import",lang(89),!$L);echo"<input type='file' name='csv_file'> ",html_select("separator",array("csv"=>"CSV,","csv;"=>"CSV;","tsv"=>"TSV"),$ka["format"],1);echo" <input type='submit' name='import' value='".lang(89)."'>","</div></fieldset>\n";}$c->selectEmailPrint(array_filter($pb,'strlen'),$h);echo"<p><input type='hidden' name='token' value='$ue'></p>\n","</form>\n";}}if(is_ajax()){ob_end_clean();exit;}}elseif(isset($_GET["script"])){if($_GET["script"]=="kill")$j->query("KILL ".(+$_POST["kill"]));elseif(list($Q,$hc,$C)=$c->_foreignColumn(column_foreign_keys($_GET["source"]),$_GET["field"])){$z=11;$I=$j->query("SELECT $hc, $C FROM ".table($Q)." WHERE ".(ereg('^[0-9]+$',$_GET["value"])?"$hc = $_GET[value] OR ":"")."$C LIKE ".q("$_GET[value]%")." ORDER BY 2 LIMIT $z");for($t=1;($K=$I->fetch_row())&&$t<$z;$t++)echo"<a href='".h(ME."edit=".urlencode($Q)."&where".urlencode("[".bracket_escape(idf_unescape($hc))."]")."=".urlencode($K[0]))."'>".h($K[1])."</a><br>\n";if($K)echo"...\n";}exit;}else{page_header(lang(50),"",false);if($c->homepage()){echo"<form action='' method='post'>\n","<p>".lang(90).": <input name='query' value='".h($_POST["query"])."'> <input type='submit' value='".lang(31)."'>\n";if($_POST["query"]!="")search_tables();echo"<table cellspacing='0' class='nowrap checkable' onclick='tableClick(event);'>\n",'<thead><tr class="wrap"><td><input id="check-all" type="checkbox" onclick="formCheck(this, /^tables\[/);"><th>'.lang(91).'<td>'.lang(92)."</thead>\n";foreach(table_status()as$Q=>$K){$C=$c->tableName($K);if(isset($K["Engine"])&&$C!=""){echo'<tr'.odd().'><td>'.checkbox("tables[]",$Q,in_array($Q,(array)$_POST["tables"],true),"","formUncheck('check-all');"),"<th><a href='".h(ME).'select='.urlencode($Q)."'>$C</a>";$X=number_format($K["Rows"],0,'.',lang(8));echo"<td align='right'><a href='".h(ME."edit=").urlencode($Q)."'>".($K["Engine"]=="InnoDB"&&$X?"~ $X":$X)."</a>";}}echo"</table>\n","<script type='text/javascript'>tableCheck();</script>\n","</form>\n";}}page_footer();
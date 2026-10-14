<?php

namespace App\Helpers;

/**
 * Every nationality the employee "Nationality" field can hold.
 *
 * The value stored in employees.employeeNationality is the Thai name (index 0),
 * exactly as before — เมียนมา / ลาว / กัมพูชา / เวียดนาม keep working in every
 * filter and business rule. The other columns are only for display and search:
 *   [0] Thai (stored value) · [1] English nationality · [2] English country
 *   [3] Myanmar · [4] Chinese
 *
 * Used by the searchable nationality picker (components/nationality-select,
 * public/js/nationality-select.js) and to show the name in the user's language.
 */
class NationalityList
{
    /** The four nationalities the program mainly works with — listed first. */
    public const MAIN = ['MM', 'LA', 'KH', 'VN'];

    public const DATA = [
        'MM' => ['เมียนมา', 'Myanmar', 'Myanmar', 'မြန်မာ', '缅甸'],
        'LA' => ['ลาว', 'Lao', 'Laos', 'လာအို', '老挝'],
        'KH' => ['กัมพูชา', 'Cambodian', 'Cambodia', 'ကမ္ဘောဒီးယား', '柬埔寨'],
        'VN' => ['เวียดนาม', 'Vietnamese', 'Vietnam', 'ဗီယက်နမ်', '越南'],
        'TH' => ['ไทย', 'Thai', 'Thailand', 'ထိုင်း', '泰国'],
        'AF' => ['อัฟกานิสถาน', 'Afghan', 'Afghanistan', 'အာဖဂန်နစ္စတန်', '阿富汗'],
        'AL' => ['แอลเบเนีย', 'Albanian', 'Albania', 'အယ်လ်ဘေးနီးယား', '阿尔巴尼亚'],
        'DZ' => ['แอลจีเรีย', 'Algerian', 'Algeria', 'အယ်လ်ဂျီးရီးယား', '阿尔及利亚'],
        'AD' => ['อันดอร์รา', 'Andorran', 'Andorra', 'အန်ဒိုရာ', '安道尔'],
        'AO' => ['แองโกลา', 'Angolan', 'Angola', 'အန်ဂိုလာ', '安哥拉'],
        'AG' => ['แอนติกาและบาร์บูดา', 'Antiguan', 'Antigua and Barbuda', 'အန်တီဂွါနှင့် ဘာဘူဒါ', '安提瓜和巴布达'],
        'AR' => ['อาร์เจนตินา', 'Argentine', 'Argentina', 'အာဂျင်တီးနား', '阿根廷'],
        'AM' => ['อาร์มีเนีย', 'Armenian', 'Armenia', 'အာမေးနီးယား', '亚美尼亚'],
        'AU' => ['ออสเตรเลีย', 'Australian', 'Australia', 'ဩစတြေးလျ', '澳大利亚'],
        'AT' => ['ออสเตรีย', 'Austrian', 'Austria', 'ဩစတြီးယား', '奥地利'],
        'AZ' => ['อาเซอร์ไบจาน', 'Azerbaijani', 'Azerbaijan', 'အဇာဘိုင်ဂျန်', '阿塞拜疆'],
        'BS' => ['บาฮามาส', 'Bahamian', 'Bahamas', 'ဘဟားမား', '巴哈马'],
        'BH' => ['บาห์เรน', 'Bahraini', 'Bahrain', 'ဘာရိန်း', '巴林'],
        'BD' => ['บังกลาเทศ', 'Bangladeshi', 'Bangladesh', 'ဘင်္ဂလားဒေ့ရှ်', '孟加拉国'],
        'BB' => ['บาร์เบโดส', 'Barbadian', 'Barbados', 'ဘာဘေးဒိုးစ်', '巴巴多斯'],
        'BY' => ['เบลารุส', 'Belarusian', 'Belarus', 'ဘီလာရုစ်', '白俄罗斯'],
        'BE' => ['เบลเยียม', 'Belgian', 'Belgium', 'ဘယ်လ်ဂျီယမ်', '比利时'],
        'BZ' => ['เบลีซ', 'Belizean', 'Belize', 'ဘလိဇ်', '伯利兹'],
        'BJ' => ['เบนิน', 'Beninese', 'Benin', 'ဘီနင်', '贝宁'],
        'BT' => ['ภูฏาน', 'Bhutanese', 'Bhutan', 'ဘူတန်', '不丹'],
        'BO' => ['โบลิเวีย', 'Bolivian', 'Bolivia', 'ဘိုလီးဗီးယား', '玻利维亚'],
        'BA' => ['บอสเนียและเฮอร์เซโกวีนา', 'Bosnian', 'Bosnia and Herzegovina', 'ဘော့စနီးယားနှင့် ဟာဇီဂိုဗီးနား', '波斯尼亚和黑塞哥维那'],
        'BW' => ['บอตสวานา', 'Botswanan', 'Botswana', 'ဘော့ဆွာနာ', '博茨瓦纳'],
        'BR' => ['บราซิล', 'Brazilian', 'Brazil', 'ဘရာဇီး', '巴西'],
        'BN' => ['บรูไน', 'Bruneian', 'Brunei', 'ဘရူနိုင်း', '文莱'],
        'BG' => ['บัลแกเรีย', 'Bulgarian', 'Bulgaria', 'ဘူလ်ဂေးရီးယား', '保加利亚'],
        'BF' => ['บูร์กินาฟาโซ', 'Burkinabe', 'Burkina Faso', 'ဘာကီးနား ဖားဆို', '布基纳法索'],
        'BI' => ['บุรุนดี', 'Burundian', 'Burundi', 'ဘူရွန်ဒီ', '布隆迪'],
        'CV' => ['กาบูเวร์ดี', 'Cape Verdean', 'Cape Verde', 'ကိတ်ဗာဒီ', '佛得角'],
        'CM' => ['แคเมอรูน', 'Cameroonian', 'Cameroon', 'ကင်မရွန်း', '喀麦隆'],
        'CA' => ['แคนาดา', 'Canadian', 'Canada', 'ကနေဒါ', '加拿大'],
        'CF' => ['สาธารณรัฐแอฟริกากลาง', 'Central African', 'Central African Republic', 'ဗဟိုအာဖရိက သမ္မတနိုင်ငံ', '中非'],
        'TD' => ['ชาด', 'Chadian', 'Chad', 'ချဒ်', '乍得'],
        'CL' => ['ชิลี', 'Chilean', 'Chile', 'ချီလီ', '智利'],
        'CN' => ['จีน', 'Chinese', 'China', 'တရုတ်', '中国'],
        'CO' => ['โคลอมเบีย', 'Colombian', 'Colombia', 'ကိုလံဘီယာ', '哥伦比亚'],
        'KM' => ['คอโมโรส', 'Comorian', 'Comoros', 'ကိုမိုရိုစ်', '科摩罗'],
        'CG' => ['คองโก', 'Congolese (Congo)', 'Republic of the Congo', 'ကွန်ဂို', '刚果(布)'],
        'CD' => ['สาธารณรัฐประชาธิปไตยคองโก', 'Congolese (DR Congo)', 'DR Congo', 'ကွန်ဂို ဒီမိုကရက်တစ် သမ္မတနိုင်ငံ', '刚果(金)'],
        'CR' => ['คอสตาริกา', 'Costa Rican', 'Costa Rica', 'ကော့စတာရီကာ', '哥斯达黎加'],
        'CI' => ['โกตดิวัวร์', 'Ivorian', "Côte d'Ivoire", 'ကို့တ်ဒီဗွာ', '科特迪瓦'],
        'HR' => ['โครเอเชีย', 'Croatian', 'Croatia', 'ခရိုအေးရှား', '克罗地亚'],
        'CU' => ['คิวบา', 'Cuban', 'Cuba', 'ကျူးဘား', '古巴'],
        'CY' => ['ไซปรัส', 'Cypriot', 'Cyprus', 'ဆိုက်ပရပ်စ်', '塞浦路斯'],
        'CZ' => ['เช็กเกีย', 'Czech', 'Czechia', 'ချက်', '捷克'],
        'DK' => ['เดนมาร์ก', 'Danish', 'Denmark', 'ဒိန်းမတ်', '丹麦'],
        'DJ' => ['จิบูตี', 'Djiboutian', 'Djibouti', 'ဂျီဘူတီ', '吉布提'],
        'DM' => ['โดมินิกา', 'Dominican (Dominica)', 'Dominica', 'ဒိုမီနီကာ', '多米尼克'],
        'DO' => ['สาธารณรัฐโดมินิกัน', 'Dominican', 'Dominican Republic', 'ဒိုမီနီကန် သမ္မတနိုင်ငံ', '多米尼加'],
        'EC' => ['เอกวาดอร์', 'Ecuadorian', 'Ecuador', 'အီကွေဒေါ', '厄瓜多尔'],
        'EG' => ['อียิปต์', 'Egyptian', 'Egypt', 'အီဂျစ်', '埃及'],
        'SV' => ['เอลซัลวาดอร์', 'Salvadoran', 'El Salvador', 'အယ်လ်ဆာဗေးဒေါ', '萨尔瓦多'],
        'GQ' => ['อิเควทอเรียลกินี', 'Equatorial Guinean', 'Equatorial Guinea', 'အီကွေတာ ဂီနီ', '赤道几内亚'],
        'ER' => ['เอริเทรีย', 'Eritrean', 'Eritrea', 'အီရီထရီးယား', '厄立特里亚'],
        'EE' => ['เอสโตเนีย', 'Estonian', 'Estonia', 'အက်စ်တိုးနီးယား', '爱沙尼亚'],
        'SZ' => ['เอสวาตีนี', 'Swazi', 'Eswatini', 'ဆွာဇီလန်', '斯威士兰'],
        'ET' => ['เอธิโอเปีย', 'Ethiopian', 'Ethiopia', 'အီသီယိုးပီးယား', '埃塞俄比亚'],
        'FJ' => ['ฟีจี', 'Fijian', 'Fiji', 'ဖီဂျီ', '斐济'],
        'FI' => ['ฟินแลนด์', 'Finnish', 'Finland', 'ဖင်လန်', '芬兰'],
        'FR' => ['ฝรั่งเศส', 'French', 'France', 'ပြင်သစ်', '法国'],
        'GA' => ['กาบอง', 'Gabonese', 'Gabon', 'ဂါဘွန်', '加蓬'],
        'GM' => ['แกมเบีย', 'Gambian', 'Gambia', 'ဂမ်ဘီယာ', '冈比亚'],
        'GE' => ['จอร์เจีย', 'Georgian', 'Georgia', 'ဂျော်ဂျီယာ', '格鲁吉亚'],
        'DE' => ['เยอรมนี', 'German', 'Germany', 'ဂျာမနီ', '德国'],
        'GH' => ['กานา', 'Ghanaian', 'Ghana', 'ဂါနာ', '加纳'],
        'GR' => ['กรีซ', 'Greek', 'Greece', 'ဂရိ', '希腊'],
        'GD' => ['เกรเนดา', 'Grenadian', 'Grenada', 'ဂရီနေဒါ', '格林纳达'],
        'GT' => ['กัวเตมาลา', 'Guatemalan', 'Guatemala', 'ဂွါတီမာလာ', '危地马拉'],
        'GN' => ['กินี', 'Guinean', 'Guinea', 'ဂီနီ', '几内亚'],
        'GW' => ['กินี-บิสเซา', 'Bissau-Guinean', 'Guinea-Bissau', 'ဂီနီ-ဘီစော', '几内亚比绍'],
        'GY' => ['กายอานา', 'Guyanese', 'Guyana', 'ဂိုင်ယာနာ', '圭亚那'],
        'HT' => ['เฮติ', 'Haitian', 'Haiti', 'ဟေတီ', '海地'],
        'HN' => ['ฮอนดูรัส', 'Honduran', 'Honduras', 'ဟွန်ဒူးရပ်စ်', '洪都拉斯'],
        'HK' => ['ฮ่องกง', 'Hong Konger', 'Hong Kong', 'ဟောင်ကောင်', '中国香港'],
        'HU' => ['ฮังการี', 'Hungarian', 'Hungary', 'ဟန်ဂေရီ', '匈牙利'],
        'IS' => ['ไอซ์แลนด์', 'Icelandic', 'Iceland', 'အိုက်စလန်', '冰岛'],
        'IN' => ['อินเดีย', 'Indian', 'India', 'အိန္ဒိယ', '印度'],
        'ID' => ['อินโดนีเซีย', 'Indonesian', 'Indonesia', 'အင်ဒိုနီးရှား', '印度尼西亚'],
        'IR' => ['อิหร่าน', 'Iranian', 'Iran', 'အီရန်', '伊朗'],
        'IQ' => ['อิรัก', 'Iraqi', 'Iraq', 'အီရတ်', '伊拉克'],
        'IE' => ['ไอร์แลนด์', 'Irish', 'Ireland', 'အိုင်ယာလန်', '爱尔兰'],
        'IL' => ['อิสราเอล', 'Israeli', 'Israel', 'အစ္စရေး', '以色列'],
        'IT' => ['อิตาลี', 'Italian', 'Italy', 'အီတလီ', '意大利'],
        'JM' => ['จาเมกา', 'Jamaican', 'Jamaica', 'ဂျမေကာ', '牙买加'],
        'JP' => ['ญี่ปุ่น', 'Japanese', 'Japan', 'ဂျပန်', '日本'],
        'JO' => ['จอร์แดน', 'Jordanian', 'Jordan', 'ဂျော်ဒန်', '约旦'],
        'KZ' => ['คาซัคสถาน', 'Kazakh', 'Kazakhstan', 'ကာဇက်စတန်', '哈萨克斯坦'],
        'KE' => ['เคนยา', 'Kenyan', 'Kenya', 'ကင်ညာ', '肯尼亚'],
        'KI' => ['คิริบาส', 'I-Kiribati', 'Kiribati', 'ကီရီဘာတီ', '基里巴斯'],
        'KP' => ['เกาหลีเหนือ', 'North Korean', 'North Korea', 'မြောက်ကိုရီးယား', '朝鲜'],
        'KR' => ['เกาหลีใต้', 'South Korean', 'South Korea', 'တောင်ကိုရီးယား', '韩国'],
        'XK' => ['โคโซโว', 'Kosovar', 'Kosovo', 'ကိုဆိုဗို', '科索沃'],
        'KW' => ['คูเวต', 'Kuwaiti', 'Kuwait', 'ကူဝိတ်', '科威特'],
        'KG' => ['คีร์กีซสถาน', 'Kyrgyz', 'Kyrgyzstan', 'ကာဂျစ္စတန်', '吉尔吉斯斯坦'],
        'LV' => ['ลัตเวีย', 'Latvian', 'Latvia', 'လတ်ဗီးယား', '拉脱维亚'],
        'LB' => ['เลบานอน', 'Lebanese', 'Lebanon', 'လက်ဘနွန်', '黎巴嫩'],
        'LS' => ['เลโซโท', 'Basotho', 'Lesotho', 'လီဆိုသို', '莱索托'],
        'LR' => ['ไลบีเรีย', 'Liberian', 'Liberia', 'လိုက်ဘေးရီးယား', '利比里亚'],
        'LY' => ['ลิเบีย', 'Libyan', 'Libya', 'လစ်ဗျား', '利比亚'],
        'LI' => ['ลิกเตนสไตน์', 'Liechtensteiner', 'Liechtenstein', 'လစ်ချ်တင်စတိုင်း', '列支敦士登'],
        'LT' => ['ลิทัวเนีย', 'Lithuanian', 'Lithuania', 'လစ်သူယေးနီးယား', '立陶宛'],
        'LU' => ['ลักเซมเบิร์ก', 'Luxembourgish', 'Luxembourg', 'လူဇင်ဘတ်', '卢森堡'],
        'MO' => ['มาเก๊า', 'Macanese', 'Macau', 'မကာအို', '中国澳门'],
        'MG' => ['มาดากัสการ์', 'Malagasy', 'Madagascar', 'မဒါဂတ်စကား', '马达加斯加'],
        'MW' => ['มาลาวี', 'Malawian', 'Malawi', 'မာလာဝီ', '马拉维'],
        'MY' => ['มาเลเซีย', 'Malaysian', 'Malaysia', 'မလေးရှား', '马来西亚'],
        'MV' => ['มัลดีฟส์', 'Maldivian', 'Maldives', 'မော်လ်ဒိုက်', '马尔代夫'],
        'ML' => ['มาลี', 'Malian', 'Mali', 'မာလီ', '马里'],
        'MT' => ['มอลตา', 'Maltese', 'Malta', 'မော်လ်တာ', '马耳他'],
        'MH' => ['หมู่เกาะมาร์แชลล์', 'Marshallese', 'Marshall Islands', 'မာရှယ်ကျွန်းစု', '马绍尔群岛'],
        'MR' => ['มอริเตเนีย', 'Mauritanian', 'Mauritania', 'မော်ရီတေးနီးယား', '毛里塔尼亚'],
        'MU' => ['มอริเชียส', 'Mauritian', 'Mauritius', 'မောရစ်ရှ', '毛里求斯'],
        'MX' => ['เม็กซิโก', 'Mexican', 'Mexico', 'မက္ကဆီကို', '墨西哥'],
        'FM' => ['ไมโครนีเซีย', 'Micronesian', 'Micronesia', 'မိုက်ခရိုနီးရှား', '密克罗尼西亚'],
        'MD' => ['มอลโดวา', 'Moldovan', 'Moldova', 'မော်လ်ဒိုဗာ', '摩尔多瓦'],
        'MC' => ['โมนาโก', 'Monégasque', 'Monaco', 'မိုနာကို', '摩纳哥'],
        'MN' => ['มองโกเลีย', 'Mongolian', 'Mongolia', 'မွန်ဂိုလီးယား', '蒙古'],
        'ME' => ['มอนเตเนโกร', 'Montenegrin', 'Montenegro', 'မွန်တီနိဂရိုး', '黑山'],
        'MA' => ['โมร็อกโก', 'Moroccan', 'Morocco', 'မော်ရိုကို', '摩洛哥'],
        'MZ' => ['โมซัมบิก', 'Mozambican', 'Mozambique', 'မိုဇမ်ဘစ်', '莫桑比克'],
        'NA' => ['นามิเบีย', 'Namibian', 'Namibia', 'နမီးဘီးယား', '纳米比亚'],
        'NR' => ['นาอูรู', 'Nauruan', 'Nauru', 'နော်ရူး', '瑙鲁'],
        'NP' => ['เนปาล', 'Nepali', 'Nepal', 'နီပေါ', '尼泊尔'],
        'NL' => ['เนเธอร์แลนด์', 'Dutch', 'Netherlands', 'နယ်သာလန်', '荷兰'],
        'NZ' => ['นิวซีแลนด์', 'New Zealander', 'New Zealand', 'နယူးဇီလန်', '新西兰'],
        'NI' => ['นิการากัว', 'Nicaraguan', 'Nicaragua', 'နီကာရာဂွါ', '尼加拉瓜'],
        'NE' => ['ไนเจอร์', 'Nigerien', 'Niger', 'နိုင်ဂျာ', '尼日尔'],
        'NG' => ['ไนจีเรีย', 'Nigerian', 'Nigeria', 'နိုင်ဂျီးရီးယား', '尼日利亚'],
        'MK' => ['มาซิโดเนียเหนือ', 'Macedonian', 'North Macedonia', 'မြောက်မက်ဆီဒိုးနီးယား', '北马其顿'],
        'NO' => ['นอร์เวย์', 'Norwegian', 'Norway', 'နော်ဝေ', '挪威'],
        'OM' => ['โอมาน', 'Omani', 'Oman', 'အိုမန်', '阿曼'],
        'PK' => ['ปากีสถาน', 'Pakistani', 'Pakistan', 'ပါကစ္စတန်', '巴基斯坦'],
        'PW' => ['ปาเลา', 'Palauan', 'Palau', 'ပလာအို', '帕劳'],
        'PS' => ['ปาเลสไตน์', 'Palestinian', 'Palestine', 'ပါလက်စတိုင်း', '巴勒斯坦'],
        'PA' => ['ปานามา', 'Panamanian', 'Panama', 'ပနားမား', '巴拿马'],
        'PG' => ['ปาปัวนิวกินี', 'Papua New Guinean', 'Papua New Guinea', 'ပါပူအာ နယူးဂီနီ', '巴布亚新几内亚'],
        'PY' => ['ปารากวัย', 'Paraguayan', 'Paraguay', 'ပါရာဂွေး', '巴拉圭'],
        'PE' => ['เปรู', 'Peruvian', 'Peru', 'ပီရူး', '秘鲁'],
        'PH' => ['ฟิลิปปินส์', 'Filipino', 'Philippines', 'ဖိလစ်ပိုင်', '菲律宾'],
        'PL' => ['โปแลนด์', 'Polish', 'Poland', 'ပိုလန်', '波兰'],
        'PT' => ['โปรตุเกส', 'Portuguese', 'Portugal', 'ပေါ်တူဂီ', '葡萄牙'],
        'QA' => ['กาตาร์', 'Qatari', 'Qatar', 'ကာတာ', '卡塔尔'],
        'RO' => ['โรมาเนีย', 'Romanian', 'Romania', 'ရိုမေးနီးယား', '罗马尼亚'],
        'RU' => ['รัสเซีย', 'Russian', 'Russia', 'ရုရှား', '俄罗斯'],
        'RW' => ['รวันดา', 'Rwandan', 'Rwanda', 'ရဝမ်ဒါ', '卢旺达'],
        'KN' => ['เซนต์คิตส์และเนวิส', 'Kittitian', 'Saint Kitts and Nevis', 'စိန့်ကစ်နှင့် နီဗစ်', '圣基茨和尼维斯'],
        'LC' => ['เซนต์ลูเซีย', 'Saint Lucian', 'Saint Lucia', 'စိန့်လူးရှား', '圣卢西亚'],
        'VC' => ['เซนต์วินเซนต์และเกรนาดีนส์', 'Vincentian', 'Saint Vincent and the Grenadines', 'စိန့်ဗင်းဆင့်နှင့် ဂရီနေဒိုင်း', '圣文森特和格林纳丁斯'],
        'WS' => ['ซามัว', 'Samoan', 'Samoa', 'ဆမိုအာ', '萨摩亚'],
        'SM' => ['ซานมาริโน', 'Sammarinese', 'San Marino', 'ဆန်မာရီနို', '圣马力诺'],
        'ST' => ['เซาตูเมและปรินซิปี', 'Santomean', 'São Tomé and Príncipe', 'ဆောင်တူမေးနှင့် ပရင်စီပီ', '圣多美和普林西比'],
        'SA' => ['ซาอุดีอาระเบีย', 'Saudi', 'Saudi Arabia', 'ဆော်ဒီအာရေးဘီးယား', '沙特阿拉伯'],
        'SN' => ['เซเนกัล', 'Senegalese', 'Senegal', 'ဆီနီဂေါ', '塞内加尔'],
        'RS' => ['เซอร์เบีย', 'Serbian', 'Serbia', 'ဆားဘီးယား', '塞尔维亚'],
        'SC' => ['เซเชลส์', 'Seychellois', 'Seychelles', 'ဆေးရှဲ', '塞舌尔'],
        'SL' => ['เซียร์ราลีโอน', 'Sierra Leonean', 'Sierra Leone', 'ဆီယာရာလီယွန်း', '塞拉利昂'],
        'SG' => ['สิงคโปร์', 'Singaporean', 'Singapore', 'စင်္ကာပူ', '新加坡'],
        'SK' => ['สโลวาเกีย', 'Slovak', 'Slovakia', 'ဆလိုဗက်ကီးယား', '斯洛伐克'],
        'SI' => ['สโลวีเนีย', 'Slovenian', 'Slovenia', 'ဆလိုဗေးနီးယား', '斯洛文尼亚'],
        'SB' => ['หมู่เกาะโซโลมอน', 'Solomon Islander', 'Solomon Islands', 'ဆော်လမွန်ကျွန်းစု', '所罗门群岛'],
        'SO' => ['โซมาเลีย', 'Somali', 'Somalia', 'ဆိုမာလီယာ', '索马里'],
        'ZA' => ['แอฟริกาใต้', 'South African', 'South Africa', 'တောင်အာဖရိက', '南非'],
        'SS' => ['ซูดานใต้', 'South Sudanese', 'South Sudan', 'တောင်ဆူဒန်', '南苏丹'],
        'ES' => ['สเปน', 'Spanish', 'Spain', 'စပိန်', '西班牙'],
        'LK' => ['ศรีลังกา', 'Sri Lankan', 'Sri Lanka', 'သီရိလင်္ကာ', '斯里兰卡'],
        'SD' => ['ซูดาน', 'Sudanese', 'Sudan', 'ဆူဒန်', '苏丹'],
        'SR' => ['ซูรินาม', 'Surinamese', 'Suriname', 'ဆူရီနမ်', '苏里南'],
        'SE' => ['สวีเดน', 'Swedish', 'Sweden', 'ဆွီဒင်', '瑞典'],
        'CH' => ['สวิตเซอร์แลนด์', 'Swiss', 'Switzerland', 'ဆွစ်ဇာလန်', '瑞士'],
        'SY' => ['ซีเรีย', 'Syrian', 'Syria', 'ဆီးရီးယား', '叙利亚'],
        'TW' => ['ไต้หวัน', 'Taiwanese', 'Taiwan', 'ထိုင်ဝမ်', '中国台湾'],
        'TJ' => ['ทาจิกิสถาน', 'Tajik', 'Tajikistan', 'တာဂျစ်ကစ္စတန်', '塔吉克斯坦'],
        'TZ' => ['แทนซาเนีย', 'Tanzanian', 'Tanzania', 'တန်ဇန်းနီးယား', '坦桑尼亚'],
        'TL' => ['ติมอร์-เลสเต', 'Timorese', 'Timor-Leste', 'အရှေ့တီမော', '东帝汶'],
        'TG' => ['โตโก', 'Togolese', 'Togo', 'တိုဂို', '多哥'],
        'TO' => ['ตองกา', 'Tongan', 'Tonga', 'တုံဂါ', '汤加'],
        'TT' => ['ตรินิแดดและโตเบโก', 'Trinidadian', 'Trinidad and Tobago', 'ထရီနီဒက်နှင့် တိုဘက်ဂို', '特立尼达和多巴哥'],
        'TN' => ['ตูนิเซีย', 'Tunisian', 'Tunisia', 'တူနီးရှား', '突尼斯'],
        'TR' => ['ตุรกี', 'Turkish', 'Türkiye', 'တူရကီ', '土耳其'],
        'TM' => ['เติร์กเมนิสถาน', 'Turkmen', 'Turkmenistan', 'တာ့ခ်မင်နစ္စတန်', '土库曼斯坦'],
        'TV' => ['ตูวาลู', 'Tuvaluan', 'Tuvalu', 'တူဗားလူ', '图瓦卢'],
        'UG' => ['ยูกันดา', 'Ugandan', 'Uganda', 'ယူဂန်ဒါ', '乌干达'],
        'UA' => ['ยูเครน', 'Ukrainian', 'Ukraine', 'ယူကရိန်း', '乌克兰'],
        'AE' => ['สหรัฐอาหรับเอมิเรตส์', 'Emirati', 'United Arab Emirates', 'အာရပ်စော်ဘွားများ ပြည်ထောင်စု', '阿联酋'],
        'GB' => ['สหราชอาณาจักร', 'British', 'United Kingdom', 'ယူနိုက်တက်ကင်းဒမ်း', '英国'],
        'US' => ['สหรัฐอเมริกา', 'American', 'United States', 'အမေရိကန်', '美国'],
        'UY' => ['อุรุกวัย', 'Uruguayan', 'Uruguay', 'ဥရုဂွေး', '乌拉圭'],
        'UZ' => ['อุซเบกิสถาน', 'Uzbek', 'Uzbekistan', 'ဥဇဘက်ကစ္စတန်', '乌兹别克斯坦'],
        'VU' => ['วานูอาตู', 'Ni-Vanuatu', 'Vanuatu', 'ဗနွားတူ', '瓦努阿图'],
        'VA' => ['นครรัฐวาติกัน', 'Vatican', 'Vatican City', 'ဗာတီကန်', '梵蒂冈'],
        'VE' => ['เวเนซุเอลา', 'Venezuelan', 'Venezuela', 'ဗင်နီဇွဲလား', '委内瑞拉'],
        'YE' => ['เยเมน', 'Yemeni', 'Yemen', 'ယီမင်', '也门'],
        'ZM' => ['แซมเบีย', 'Zambian', 'Zambia', 'ဇမ်ဘီယာ', '赞比亚'],
        'ZW' => ['ซิมบับเว', 'Zimbabwean', 'Zimbabwe', 'ဇင်ဘာဘွေ', '津巴布韦'],
    ];

    /** Column for each UI language. */
    protected const LOCALE_COLUMN = ['th' => 0, 'en' => 1, 'my' => 3, 'zh' => 4];

    /** [stored Thai value => code], built once. */
    protected static ?array $byThai = null;

    public static function codeFor(?string $stored): ?string
    {
        if ($stored === null || trim($stored) === '') {
            return null;
        }
        if (self::$byThai === null) {
            self::$byThai = [];
            foreach (self::DATA as $code => $row) {
                self::$byThai[$row[0]] = $code;
            }
        }

        return self::$byThai[trim($stored)] ?? null;
    }

    /** The stored (Thai) value shown in the given / current UI language; unknown values come back unchanged. */
    public static function label(?string $stored, ?string $locale = null): string
    {
        $code = self::codeFor($stored);
        if (! $code) {
            return (string) $stored;
        }
        $col = self::LOCALE_COLUMN[$locale ?? app()->getLocale()] ?? 1;

        return self::DATA[$code][$col];
    }

    /**
     * Sort key without the intl extension: Thai words starting with a leading
     * vowel (เ แ โ ใ ไ) sort by their first consonant, as in a Thai dictionary.
     */
    protected static function sortKey(string $label): string
    {
        $first = mb_substr($label, 0, 1);
        if (in_array($first, ['เ', 'แ', 'โ', 'ใ', 'ไ'], true)) {
            return mb_substr($label, 1, 1) . $first . mb_substr($label, 2);
        }

        return mb_strtolower($label);
    }

    /**
     * Options for a picker: main nationalities first, then the rest sorted by
     * the name in the current language. Each: value (Thai), label, search text, main.
     */
    public static function options(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $col = self::LOCALE_COLUMN[$locale] ?? 1;
        $main = [];
        $rest = [];
        foreach (self::DATA as $code => $row) {
            $item = [
                'value' => $row[0],
                'label' => $row[$col],
                'search' => mb_strtolower(implode(' ', [$row[0], $row[1], $row[2], $row[3], $row[4], $code])),
                'main' => in_array($code, self::MAIN, true),
            ];
            if ($item['main']) {
                $main[array_search($code, self::MAIN, true)] = $item;
            } else {
                $rest[] = $item;
            }
        }
        ksort($main);
        usort($rest, fn ($a, $b) => strcmp(self::sortKey($a['label']), self::sortKey($b['label'])));

        return array_merge(array_values($main), $rest);
    }
}

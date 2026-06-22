<?php

/**
 * Preset list of Thai banks shown in the Financial Profile bank-account
 * picker and the Tax Invoice payment-method dropdown.
 *
 * Each entry carries:
 *   - code  short identifier we persist on `bank_accounts.bank_code`
 *   - name_th / name_en  display names
 *   - color  brand hex used by the CSS "initial badge" so we don't
 *            need to ship raster logos (IP-safe + no asset weight)
 *   - initial  the badge glyph (usually the first letter of the
 *              English short name, occasionally two letters for
 *              clarity, e.g. KTB)
 *
 * Add a new bank by appending to this array — nothing else needs to
 * change. The "other" / "promptpay" types are handled separately in
 * code (they're not stored here because they don't have a preset
 * brand to look up).
 */

return [

    [
        'code' => 'BBL',
        'name_th' => 'ธนาคารกรุงเทพ',
        'name_en' => 'Bangkok Bank',
        'color' => '#1E4598',
        'initial' => 'B',
    ],
    [
        'code' => 'KBANK',
        'name_th' => 'ธนาคารกสิกรไทย',
        'name_en' => 'Kasikorn Bank',
        'color' => '#0A8A1A',
        'initial' => 'K',
    ],
    [
        'code' => 'KTB',
        'name_th' => 'ธนาคารกรุงไทย',
        'name_en' => 'Krungthai Bank',
        'color' => '#00A1DE',
        'initial' => 'KT',
    ],
    [
        'code' => 'TTB',
        'name_th' => 'ธนาคารทหารไทยธนชาต',
        'name_en' => 'TMBThanachart Bank',
        'color' => '#FA6400',
        'initial' => 'T',
    ],
    [
        'code' => 'SCB',
        'name_th' => 'ธนาคารไทยพาณิชย์',
        'name_en' => 'Siam Commercial Bank',
        'color' => '#4E2E7E',
        'initial' => 'S',
    ],
    [
        'code' => 'BAY',
        'name_th' => 'ธนาคารกรุงศรีอยุธยา',
        'name_en' => 'Bank of Ayudhya (Krungsri)',
        'color' => '#FEC43B',
        'initial' => 'A',
    ],
    [
        'code' => 'KKP',
        'name_th' => 'ธนาคารเกียรตินาคินภัทร',
        'name_en' => 'Kiatnakin Phatra Bank',
        'color' => '#0050AA',
        'initial' => 'KK',
    ],
    [
        'code' => 'CIMB',
        'name_th' => 'ธนาคารซีไอเอ็มบีไทย',
        'name_en' => 'CIMB Thai Bank',
        'color' => '#7E1E2C',
        'initial' => 'C',
    ],
    [
        'code' => 'TISCO',
        'name_th' => 'ธนาคารทิสโก้',
        'name_en' => 'TISCO Bank',
        'color' => '#15366A',
        'initial' => 'TS',
    ],
    [
        'code' => 'UOB',
        'name_th' => 'ธนาคารยูโอบี',
        'name_en' => 'United Overseas Bank',
        'color' => '#003875',
        'initial' => 'U',
    ],
    [
        'code' => 'LH',
        'name_th' => 'ธนาคารแลนด์ แอนด์ เฮ้าส์',
        'name_en' => 'Land and Houses Bank',
        'color' => '#5D8C5F',
        'initial' => 'LH',
    ],
    [
        'code' => 'ICBC',
        'name_th' => 'ธนาคารไอซีบีซี (ไทย)',
        'name_en' => 'ICBC (Thai)',
        'color' => '#C8102E',
        'initial' => 'I',
    ],
    [
        'code' => 'GSB',
        'name_th' => 'ธนาคารออมสิน',
        'name_en' => 'Government Savings Bank',
        'color' => '#E1457A',
        'initial' => 'G',
    ],
    [
        'code' => 'BAAC',
        'name_th' => 'ธนาคารเพื่อการเกษตรและสหกรณ์การเกษตร',
        'name_en' => 'Bank for Agriculture and Agricultural Cooperatives',
        'color' => '#52A832',
        'initial' => 'BA',
    ],
    [
        'code' => 'GHB',
        'name_th' => 'ธนาคารอาคารสงเคราะห์',
        'name_en' => 'Government Housing Bank',
        'color' => '#F37021',
        'initial' => 'GH',
    ],
    [
        'code' => 'EXIM',
        'name_th' => 'ธนาคารเพื่อการส่งออกและนำเข้าแห่งประเทศไทย',
        'name_en' => 'Export-Import Bank of Thailand',
        'color' => '#1B5E20',
        'initial' => 'EX',
    ],
    [
        'code' => 'ISBT',
        'name_th' => 'ธนาคารอิสลามแห่งประเทศไทย',
        'name_en' => 'Islamic Bank of Thailand',
        'color' => '#00695C',
        'initial' => 'IB',
    ],

];

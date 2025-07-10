<?php

namespace App\Helpers;
use PhpOffice\PhpWord\Shared\Html;

class HtmlToText
{
    //protected static $sectionTitles = config('candidate.language');

    // public static function insertSkillsTable($section, $data = [])
    // {
    //     $experiences = json_decode($data->skills, true);
    //     $section->addText('KỸ NĂNG', [
    //         'bold' => true,
    //         'underline' => 'single',
    //         'name' => 'Times New Roman',
    //         'size' => 12,
    //     ], [
    //         'spaceBefore' => 400, // Khoảng cách dưới tiêu đề
    //         'spaceAfter' => 400, // Khoảng cách dưới tiêu đề
    //     ]);

    //     $table = $section->addTable([
    //         'width' => 100 * 50,
    //         'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT,
    //         'borderSize' => 0,
    //         'borderColor' => 'FFFFFF',
    //     ]);

    //     $table->addRow();
    //     $table->addCell(35 * 50)->addText('Tin học', ['bold' => true]);
    //     $table->addCell(65 * 50)->addText(
    //         'Am hiểu và sử dụng thành thạo các chức năng nâng cao như định dạng văn bản, tạo bảng biểu, hàm Excel, lọc và phân tích dữ liệu',
    //         ['name' => 'Times New Roman'],
    //         ['spaceAfter' => 150]
    //     );

    //     $table->addRow();
    //     $table->addCell(35 * 50)->addText('Photoshop / Canva', ['bold' => true]);
    //     $table->addCell(65 * 50)->addText(
    //         'Thiết kế cơ bản phục vụ truyền thông, thuyết trình, Thành thạo Google Docs, Sheets, Slides.',
    //         ['name' => 'Times New Roman'],
    //         ['spaceAfter' => 150]
    //     );
    // }

    public static function insertSkillsTable($section, $data = [], $lang = 'vi')
    {
        $skills = json_decode($data->skills, true);
        if (empty($skills) || !is_array($skills)) return;
        //$title = self::$sectionTitles['skills'][$lang] ?? 'KỸ NĂNG';
        $titles = self::getSectionTitles();
        $title = $titles['skills'][$lang] ?? 'KỸ NĂNG';
        $section->addText($title, [
            'bold' => true,
            'underline' => 'single',
            'name' => 'Times New Roman',
            'size' => 12,
        ], [
            'spaceBefore' => 400,
            'spaceAfter' => 400,
        ]);

        $table = $section->addTable([
            'width' => 100 * 50,
            'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT,
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
        ]);

        foreach ($skills as $skill) {
            $table->addRow();
            $table->addCell(35 * 50)->addText($skill['name'], [
                'bold' => true,
                'name' => 'Times New Roman',
                'size' => 12,
            ]);
            $table->addCell(65 * 50)->addText($skill['description'], [
                'name' => 'Times New Roman',
                'size' => 12,
            ], [
                'spaceAfter' => 150,
            ]);
        }
    }


    public static function insertEducationBlock($section, $data = [], $lang = 'vi')
    {
        $rows = json_decode($data->time_education, true);
        if( !isset($rows) || !is_array($rows) || count($rows) == 0 ) return true;
        //$title = self::$sectionTitles['education'][$lang] ?? 'QUÁ TRÌNH HỌC TẬP';
        $titles = self::getSectionTitles();
        $title = $titles['education'][$lang] ?? 'QUÁ TRÌNH HỌC TẬP';
        $section->addText($title, [
            'bold' => true,
            'underline' => 'single',
            'name' => 'Times New Roman',
            'size' => 12,
        ], [
            'spaceAfter' => 400,
            'spaceBefore' => 400, // Khoảng cách dưới tiêu đề
        ]);

        $table = $section->addTable([
            'width' => 100 * 50,
            'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT,
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
        ]);

        // $rows = [
        //     ['time' => '9/2017 - 10/2021', 'school' => 'Trường Trung Học Phương Đông Ngôn ngữ Nhật'],
        //     ['time' => '30/2023 - 30/2024', 'school' => 'Trường Trung Học Phổ Thông Phương Tây Ngôn ngữ Nhật'],
        //     ['time' => '30/2024 - Nay', 'school' => 'Trường Đại Học Quốc Gia Hà Nội'],
        // ];

        foreach ($rows as $row) {
            $table->addRow();
            $table->addCell(35 * 50)->addText($row['time'], ['bold' => true]);
            $table->addCell(65 * 50)->addText($row['school'], ['name' => 'Times New Roman'], [
                'spaceAfter' => 150,
            ]);
        }
    }

    public static function insertInformationBlock($section, $data = [], $lang = 'vi')
    {
        $titles = self::getSectionTitles();
        $title = $titles['infomation'][$lang] ?? 'THÔNG TIN CÁ NHÂN';
        $fullname = $data->translation->full_name;
        $gender = $data->translation->gender;
        $birthday = date('d-m-Y', strtotime($data->birthday));
        $section->addText($title, [
            'bold' => true,
            'underline' => 'single',
            'name' => 'Times New Roman',
            'size' => 12,
        ], [
            'spaceAfter' => 400,
            'spaceBefore' => 400,
        ]);

        $table = $section->addTable([
            'width' => 100 * 50,
            'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT,
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
        ]);

        $info = [
            ['title' => self::subTitle('infomation', 'fullname', $lang), 'description' => $fullname ?? ''],
            ['title' => self::subTitle('infomation', 'gender', $lang), 'description' => $gender ?? ''],
            ['title' => self::subTitle('infomation', 'birthday', $lang), 'description' => $birthday ?? ''],
            ['title' => self::subTitle('infomation', 'address', $lang), 'description' => 'Đống Đa - Hà Nội' ?? ''],
        ];

        foreach ($info as $key => $row) {
            $options = [
                'name' => 'Times New Roman'
            ];
            if( $key == 0 ) {
                $options['bold'] = true;
            }
            $table->addRow();
            $table->addCell(35 * 50)->addText($row['title'], ['bold' => true]);
            $table->addCell(65 * 50)->addText($row['description'], $options, [
                'spaceAfter' => 150,
            ]);
        }

        $section->addTextBreak(1);
    }
    
    public static function insertExperienceBlock($section, $data = [], $lang = 'vi')
    {
        $experiences = json_decode($data->work_experience, true);
        if( !isset($experiences) || !is_array($experiences) || count($experiences) == 0 ) return true;
        $titles = self::getSectionTitles();
        $title = $titles['experience'][$lang] ?? 'KINH NGHIỆM LÀM VIỆC';
        $section->addText($title, [
            'bold' => true,
            'underline' => 'single',
            'name' => 'Times New Roman',
            'size' => 12,
        ], [
            'spaceAfter' => 400,
            'spaceBefore' => 400, // Khoảng cách dưới tiêu đề
        ]);

        $companyTitle = self::subTitle('experience', 'company', $lang);
        $positionTitle = self::subTitle('experience', 'position', $lang);
        $descriptionTitle = self::subTitle('experience', 'description', $lang);

        // $experiences = [
        //     [
        //         'time' => '06/2020 - 09/2023',
        //         'company' => 'CÔNG TY TNHH GLOBAL SOURCENET',
        //         'position' => 'Nhân viên xuất nhập khẩu (Mạnh về xuất khẩu)',
        //         'duties' => [
        //             'Nhận thông tin và book các lô hàng xuất.',
        //             'Chuẩn bị hồ sơ chứng từ.',
        //             'Thực hiện khai và truyền tờ khai trên phần mềm khai báo hải quan.',
        //             'Chuẩn bị hồ sơ và thực hiện khai báo C/O form E, VK, VJ, B, EUR1',
        //             'Làm việc với Forwarder để hoàn thành giao các lô hàng nhanh nhất.',
        //             'Theo dõi, quản lý tiến độ của hàng hóa và kiểm soát số lượng của sản phẩm.',
        //             'Giải quyết những vấn đề phát sinh có liên quan đến hàng hóa, sản phẩm trong quá trình vận chuyển.',
        //             'Quản lý, lưu trữ các chứng từ có liên quan,…',
        //         ]
        //     ],
        //     [
        //         'time' => '09/2023 - Hiện tại',
        //         'company' => 'CÔNG TY TNHH GLOBAL SOURCENET',
        //         'position' => 'Nhân viên xuất nhập khẩu (Mạnh về xuất khẩu)',
        //         'duties' => [
        //             'Nhận thông tin và book các lô hàng xuất.',
        //             'Chuẩn bị hồ sơ chứng từ.',
        //             'Thực hiện khai và truyền tờ khai trên phần mềm khai báo hải quan.',
        //             'Chuẩn bị hồ sơ và thực hiện khai báo C/O form E, VK, VJ, B, EUR1',
        //             'Làm việc với Forwarder để hoàn thành giao các lô hàng nhanh nhất.',
        //             'Theo dõi, quản lý tiến độ của hàng hóa và kiểm soát số lượng của sản phẩm.',
        //             'Giải quyết những vấn đề phát sinh có liên quan đến hàng hóa, sản phẩm trong quá trình vận chuyển.',
        //             'Quản lý, lưu trữ các chứng từ có liên quan,…',
        //         ]
        //     ],
        // ];

        foreach ($experiences as $exp) {
            // Table: Time | Company + Position
            $table = $section->addTable([
                'width' => 100 * 50,
                'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);

            $table->addRow();

            // Cột thời gian
            $table->addCell(35 * 50)->addText($exp['time'], [
                'bold' => true,
                'name' => 'Times New Roman',
                'size' => 12,
            ]);

            // Cột công ty + vị trí
            $cell = $table->addCell(65 * 50);
            $cell->addText($exp['company'], [
                'bold' => true,
                'name' => 'Times New Roman',
                'size' => 12,
            ]);
            $cell->addText($positionTitle.": {$exp['position']}", [
                'italic' => true,
                'bold' => true,
                'name' => 'Times New Roman',
                'size' => 12,
            ]);

            // * Nhiệm vụ
            $section->addText('* ' . $descriptionTitle . ':', [
                'bold' => true,
                'underline' => 'single',
                'name' => 'Times New Roman',
            ], [
                'spaceBefore' => 150,
                'spaceAfter' => 150,
            ]);

            Self::safeAddHtml($section, $exp['description']);

            // Danh sách bullet nhiệm vụ
            // foreach ($exp['description'] as $duty) {
            //     $section->addListItem($duty, 0, [
            //         'name' => 'Times New Roman',
            //         'size' => 12,
            //     ], [
            //         'listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED,
            //     ]);
            // }

            // Khoảng cách giữa các kinh nghiệm
            $section->addTextBreak(1);
        }
    }

    public static function insertStrengthsBlock($section, $data = [], $lang = 'vi')
    {
        //$title = self::$sectionTitles['strength'][$lang] ?? 'ĐIỂM MẠNH';
        $titles = self::getSectionTitles();
        $title = $titles['strength'][$lang] ?? 'ĐIỂM MẠNH';
        $section->addText($title, [
            'bold' => true,
            'underline' => 'single',
            'name' => 'Times New Roman',
            'size' => 12,
        ], [
            'spaceAfter' => 300,
        ]);
        
        
        Self::safeAddHtml($section, $data->strength);

        // $strengths = [
        //     'Tốt nghiệp chuyên ngành Ngôn ngữ Nhật tại Đại học Phương Đông Đông',
        //     'Có tổng 3 năm kinh nghiệm làm chuyên sâu về Xuất khẩu cho công ty chuyên gia công sản xuất quần áo thời trang nữ xuất khẩu trang thị trường EU, Mỹ, Nhật Bản… của Hàn Quốc, về phần Nhập khẩu có hiểu biết và làm phần thanh toán. Có kinh nghiệm trực tiếp khai báo hải quan, khai báo C/O form E, VK, VJ, B, EUR1 và theo dõi tiến độ hàng hóa và giải quyết những vấn đề phát sinh trong quá trình vận chuyển',
        //     'Mức lương mong muốn: VND 13.500.000 Gross (có thể thương lượng thêm)',
        //     'Thời gian bắt đầu đi làm: 1 tuần khi nhận được thông báo',
        // ];

        // Danh sách các điểm mạnh dạng bullet
        // foreach ($strengths as $strength) {
        //     $section->addListItem($strength, 0, [
        //         'name' => 'Times New Roman',
        //         'size' => 12,
        //     ], [
        //         'listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED,
        //     ]);
        // }

        // Thêm khoảng cách sau block
        $section->addTextBreak(1);
    }
    
    public static function insertPersonalInfoBlock($section, $data, $lang = 'vi')
    {
        $fullname = $data->translation->full_name;
        $gender = $data->translation->gender;
        $birthday = date('d-m-Y', strtotime($data->birthday));
        $avatarPath = $data->avatar ?? asset($data->avatar);

        $titles = self::getSectionTitles();
        $title = $titles['infomation'][$lang] ?? 'THÔNG TIN CÁ NHÂN';
        // Tiêu đề
        $section->addText($title, [
            'bold' => true,
            'underline' => 'single',
            'name' => 'Times New Roman',
            'size' => 12,
        ], [
            'spaceAfter' => 200,
        ]);

        // Bảng chính: 2 cột
        $table = $section->addTable([
            'width' => 100 * 50,
            'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT,
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 80,
        ]);
        $table->addRow();

        // === CỘT TRÁI: Wrapper để tạo khoảng cách bên trong ===
        $leftCell = $table->addCell(70 * 50, ['valign' => 'top']);

        // Tạo bảng bọc để thụt vào trong
        $wrapperTable = $leftCell->addTable([
            'width' => 100 * 50,
            'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT,
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
        ]);
        $wrapperTable->addRow();

        // Cell 1: làm khoảng trống bên trái
        $wrapperTable->addCell(5 * 50); // ~5% width

        // Cell 2: chứa bảng con thực sự
        $wrapperCell = $wrapperTable->addCell(95 * 50);

        // Bảng con: chứa thông tin cá nhân
        $infoTable = $wrapperCell->addTable([
            'width' => 100 * 50,
            'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT,
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 50,
        ]);

        $infoRows = [
            self::subTitle('infomation', 'fullname', $lang) => $fullname,
            self::subTitle('infomation', 'birthday', $lang) => $birthday,
            self::subTitle('infomation', 'gender', $lang) => $gender,
            self::subTitle('infomation', 'address', $lang)   => 'Hà nội',
        ];

        $i = 0;
        foreach ($infoRows as $label => $value) {
            $infoTable->addRow();

            $infoTable->addCell(30 * 50)->addText($label, [
                'bold' => true,
                'name' => 'Times New Roman',
                'size' => 12,
            ], [
                'lineHeight' => 1.5,
                'spaceAfter' => 100,
            ]);

            $infoTable->addCell(70 * 50)->addText(':'.$value, [
                'name' => 'Times New Roman',
                'size' => 12,
                'bold' => $i == 0 ? true : false,
            ], [
                'lineHeight' => 1.5,
                'spaceAfter' => 100,
            ]);
            $i++;
        }

        // === CỘT PHẢI: ảnh đại diện ===
        $rightCell = $table->addCell(30 * 50, ['valign' => 'top']);
        if (file_exists($avatarPath)) {
            $rightCell->addImage($avatarPath, [
                'width' => 115,
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            ]);
        } else {
            $rightCell->addText('(Không tìm thấy ảnh)', [
                'italic' => true,
                'name' => 'Times New Roman',
                'size' => 10,
            ]);
        }
    }

    public static function convert($html)
    {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();

        $body = $doc->getElementsByTagName('body')->item(0);
        return self::parseNodes($body->childNodes);
    }

    protected static function parseNodes($nodes, $level = 0)
    {
        $text = '';
        foreach ($nodes as $node) {
            if ($node->nodeName === 'ul') {
                $text .= self::parseNodes($node->childNodes, $level);
            } elseif ($node->nodeName === 'li') {
                $prefix = ($level === 0 ? '• ' : str_repeat('    ', $level) . '◦ ');
                $text .= $prefix . self::parseNodes($node->childNodes, $level + 1) . "\n";
            } elseif ($node->nodeName === 'strong' || $node->nodeName === 'b') {
                $text .= strtoupper($node->nodeValue);
            } elseif ($node->hasChildNodes()) {
                $text .= self::parseNodes($node->childNodes, $level);
            } else {
                $text .= $node->nodeValue;
            }
        }
        return $text;
    }

    public static function convertListToBulletParagraphs(string $html): string
    {
        // Kiểm tra xem <ul> có style bold không
        $ulBold = stripos($html, '<ul') !== false && stripos($html, 'font-weight: bold') !== false;

        // Lấy tất cả nội dung trong <li>
        preg_match_all('/<li(?:[^>]*)>(.*?)<\/li>/is', $html, $matches);

        $result = '';
        foreach ($matches[1] as $item) {
            $item = trim($item);

            // Kiểm tra nếu từng <li> có style bold hoặc chứa <strong>
            $liBold = stripos($item, 'font-weight: bold') !== false || stripos($item, '<strong>') !== false;

            // Nếu <ul> hoặc <li> có bold, bọc nội dung lại bằng <strong> nếu chưa có
            $hasBold = $ulBold || $liBold;

            // Nếu chưa có <strong>, thì thêm vào (để không bị lặp)
            if ($hasBold && stripos($item, '<strong>') === false) {
                $item = '<strong>' . $item . '</strong>';
            }

            $result .= '<p><strong>• </strong> ' . $item . '</p>' . PHP_EOL;
        }

        return $result;
    }

    // Lọc dữ liệu thô HTML chuyển về dạng theo Word
    public static function safeAddHtml($section, $rawHtml)
    {
        if (empty($rawHtml)) return;

        // ✅ Chuẩn hóa <br>
        $safeHtml = preg_replace('/<br\s*>/i', '<br />', $rawHtml);

        // ✅ Loại bỏ tab & &nbsp;
        $safeHtml = str_replace(['&nbsp;', "\t"], [' ', ''], $safeHtml);

        // ✅ Thay thế các ký tự đặc biệt gây lỗi
        $replaceMap = [
            '©' => '(c)',
            '®' => '(r)',
            '…' => '...',
            '“' => '"',
            '”' => '"',
            '‘' => "'",
            '&amp;' => " ",
            '’' => "'",
            '–' => '-',   // en dash
            '—' => '-',   // em dash
            '•' => '-',   // bullet
            '‐' => '-',   // hyphen unicode
        ];
        $safeHtml = strtr($safeHtml, $replaceMap);

        // ❗ Không encode lại & nếu đã là &amp;
        // => KHÔNG dùng htmlspecialchars / html_entity_encode

        // ✅ Cho phép các thẻ HTML an toàn
        $allowedTags = '<p><br><b><i><u><strong><em><ul><ol><li><table><tr><td><th><h1><h2><h3><h4><h5><h6>';
        $safeHtml = strip_tags($safeHtml, $allowedTags);

        // ✅ Add vào Word
        Html::addHtml($section, $safeHtml, false, false);
    }




    // Lấy ra subtitle trong Block chính
    public static function subTitle(string $section, string $key, string $lang = 'vi'): string
    {
        $titles = config('candidate.language');

        return $titles[$section]['sub'][$key][$lang]
            ?? ucfirst($key); // fallback nếu không có
    }

    // Lấy tiêu đề Section
    protected static function getSectionTitles(): array
    {
        return config('candidate.language', []);
    }
    
    
}

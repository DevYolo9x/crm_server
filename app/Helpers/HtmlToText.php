<?php

namespace App\Helpers;

class HtmlToText
{

    public static function insertSkillsTable($section, $data = [])
    {
        $section->addText('KỸ NĂNG', [
            'bold' => true,
            'underline' => 'single',
            'name' => 'Times New Roman',
            'size' => 12,
        ], [
            'spaceBefore' => 400, // Khoảng cách dưới tiêu đề
            'spaceAfter' => 400, // Khoảng cách dưới tiêu đề
        ]);

        $table = $section->addTable([
            'width' => 100 * 50,
            'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT,
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
        ]);

        $table->addRow();
        $table->addCell(35 * 50)->addText('Tin học', ['bold' => true]);
        $table->addCell(65 * 50)->addText(
            'Am hiểu và sử dụng thành thạo các chức năng nâng cao như định dạng văn bản, tạo bảng biểu, hàm Excel, lọc và phân tích dữ liệu',
            ['name' => 'Times New Roman'],
            ['spaceAfter' => 150]
        );

        $table->addRow();
        $table->addCell(35 * 50)->addText('Photoshop / Canva', ['bold' => true]);
        $table->addCell(65 * 50)->addText(
            'Thiết kế cơ bản phục vụ truyền thông, thuyết trình, Thành thạo Google Docs, Sheets, Slides.',
            ['name' => 'Times New Roman'],
            ['spaceAfter' => 150]
        );
    }

    public static function insertEducationBlock($section, $data = [])
    {
        $section->addText('QUÁ TRÌNH HỌC TẬP', [
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

        $rows = [
            ['time' => '9/2017 - 10/2021', 'school' => 'Trường Trung Học Phương Đông Ngôn ngữ Nhật'],
            ['time' => '30/2023 - 30/2024', 'school' => 'Trường Trung Học Phổ Thông Phương Tây Ngôn ngữ Nhật'],
            ['time' => '30/2024 - Nay', 'school' => 'Trường Đại Học Quốc Gia Hà Nội'],
        ];

        foreach ($rows as $row) {
            $table->addRow();
            $table->addCell(35 * 50)->addText($row['time'], ['bold' => true]);
            $table->addCell(65 * 50)->addText($row['school'], ['name' => 'Times New Roman'], [
                'spaceAfter' => 150,
            ]);
        }
    }

    public static function insertInformationBlock($section, $data = [])
    {
        $section->addText('THÔNG TIN CÁ NHÂN', [
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
            ['title' => 'Họ và tên', 'description' => 'TRẦN XUÂN BÌNH' ?? ''],
            ['title' => 'Giới tính', 'description' => 'Nam' ?? ''],
            ['title' => 'Ngày sinh', 'description' => '18/06/1997' ?? ''],
            ['title' => 'Địa chỉ hiện tại', 'description' => 'Đống Đa - Hà Nội' ?? ''],
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
    
    public static function insertExperienceBlock($section, $experiences = [])
    {
        $section->addText('KINH NGHIỆM LÀM VIỆC', [
            'bold' => true,
            'underline' => 'single',
            'name' => 'Times New Roman',
            'size' => 12,
        ], [
            'spaceAfter' => 400,
            'spaceBefore' => 400, // Khoảng cách dưới tiêu đề
        ]);

        $experiences = [
            [
                'time' => '06/2020 - 09/2023',
                'company' => 'CÔNG TY TNHH GLOBAL SOURCENET',
                'position' => 'Nhân viên xuất nhập khẩu (Mạnh về xuất khẩu)',
                'duties' => [
                    'Nhận thông tin và book các lô hàng xuất.',
                    'Chuẩn bị hồ sơ chứng từ.',
                    'Thực hiện khai và truyền tờ khai trên phần mềm khai báo hải quan.',
                    'Chuẩn bị hồ sơ và thực hiện khai báo C/O form E, VK, VJ, B, EUR1',
                    'Làm việc với Forwarder để hoàn thành giao các lô hàng nhanh nhất.',
                    'Theo dõi, quản lý tiến độ của hàng hóa và kiểm soát số lượng của sản phẩm.',
                    'Giải quyết những vấn đề phát sinh có liên quan đến hàng hóa, sản phẩm trong quá trình vận chuyển.',
                    'Quản lý, lưu trữ các chứng từ có liên quan,…',
                ]
            ],
            [
                'time' => '09/2023 - Hiện tại',
                'company' => 'CÔNG TY TNHH GLOBAL SOURCENET',
                'position' => 'Nhân viên xuất nhập khẩu (Mạnh về xuất khẩu)',
                'duties' => [
                    'Nhận thông tin và book các lô hàng xuất.',
                    'Chuẩn bị hồ sơ chứng từ.',
                    'Thực hiện khai và truyền tờ khai trên phần mềm khai báo hải quan.',
                    'Chuẩn bị hồ sơ và thực hiện khai báo C/O form E, VK, VJ, B, EUR1',
                    'Làm việc với Forwarder để hoàn thành giao các lô hàng nhanh nhất.',
                    'Theo dõi, quản lý tiến độ của hàng hóa và kiểm soát số lượng của sản phẩm.',
                    'Giải quyết những vấn đề phát sinh có liên quan đến hàng hóa, sản phẩm trong quá trình vận chuyển.',
                    'Quản lý, lưu trữ các chứng từ có liên quan,…',
                ]
            ],
        ];

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
            $cell->addText("Vị trí: {$exp['position']}", [
                'italic' => true,
                'bold' => true,
                'name' => 'Times New Roman',
                'size' => 12,
            ]);

            // * Nhiệm vụ
            $section->addText('* Nhiệm vụ:', [
                'bold' => true,
                'underline' => 'single',
                'name' => 'Times New Roman',
            ], [
                'spaceBefore' => 150,
                'spaceAfter' => 150,
            ]);

            // Danh sách bullet nhiệm vụ
            foreach ($exp['duties'] as $duty) {
                $section->addListItem($duty, 0, [
                    'name' => 'Times New Roman',
                    'size' => 12,
                ], [
                    'listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED,
                ]);
            }

            // Khoảng cách giữa các kinh nghiệm
            $section->addTextBreak(1);
        }
    }

    public static function insertStrengthsBlock($section, $strengths = [])
    {
        // Tiêu đề "ĐIỂM MẠNH"
        $section->addText('ĐIỂM MẠNH', [
            'bold' => true,
            'underline' => 'single',
            'name' => 'Times New Roman',
            'size' => 12,
        ], [
            'spaceAfter' => 300,
        ]);

        $strengths = [
            'Tốt nghiệp chuyên ngành Ngôn ngữ Nhật tại Đại học Phương Đông Đông',
            'Có tổng 3 năm kinh nghiệm làm chuyên sâu về Xuất khẩu cho công ty chuyên gia công sản xuất quần áo thời trang nữ xuất khẩu trang thị trường EU, Mỹ, Nhật Bản… của Hàn Quốc, về phần Nhập khẩu có hiểu biết và làm phần thanh toán. Có kinh nghiệm trực tiếp khai báo hải quan, khai báo C/O form E, VK, VJ, B, EUR1 và theo dõi tiến độ hàng hóa và giải quyết những vấn đề phát sinh trong quá trình vận chuyển',
            'Mức lương mong muốn: VND 13.500.000 Gross (có thể thương lượng thêm)',
            'Thời gian bắt đầu đi làm: 1 tuần khi nhận được thông báo',
        ];

        // Danh sách các điểm mạnh dạng bullet
        foreach ($strengths as $strength) {
            $section->addListItem($strength, 0, [
                'name' => 'Times New Roman',
                'size' => 12,
            ], [
                'listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED,
            ]);
        }

        // Thêm khoảng cách sau block
        $section->addTextBreak(1);
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
    
}

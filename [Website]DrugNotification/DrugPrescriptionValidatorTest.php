<?php

use PHPUnit\Framework\TestCase;

class DrugPrescriptionValidatorTest extends TestCase
{
    /** @var DrugCatalogRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $catalog;

    /** @var DrugPrescriptionValidator */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->catalog = $this->createMock(DrugCatalogRepository::class);
        $this->validator = new DrugPrescriptionValidator($this->catalog);
    }

    /**
     * Test Case ID: TC_S9_21
     * Description: Dòng đơn hợp lệ được chấp nhận khi thuốc có trong danh mục và đang hoạt động
     * Scenario Type: Positive
     * Expected Result: Trả kết quả ok=true và chứa drug_id, quantity
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_21
     */
    public function testValidateLineAcceptsWellFormedActiveDrug(): void
    {
        // Arrange
        $this->catalog->method('findById')->willReturnMap(array(
            array('Par1', array('id' => 'Par1', 'name' => 'Paracetamol', 'is_active' => true)),
        ));
        $item = array('drug_id' => 'Par1', 'quantity' => 2, 'dosage' => '500 mg x 2 láº§n/ngÃ y');

        // Act
        $result = $this->validator->validateLineItem($item);

        // Assert
        $this->assertTrue($result->ok);
        $this->assertSame('Par1', $result->data['drug_id']);
        $this->assertSame(2, $result->data['quantity']);
    }

    /**
     * Test Case ID: TC_S9_22
     * Description: Từ chối khi mã thuốc để trống (credential nghĩa vụ đơn hàng trống)
     * Scenario Type: Negative
     * Expected Result: ok=false và code=EMPTY_CREDENTIALS
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_22
     */
    public function testValidateLineFailsWhenDrugIdMissing(): void
    {
        // Arrange
        $this->catalog->expects($this->never())->method('findById');
        $item = array('drug_id' => '   ', 'quantity' => 1, 'dosage' => '10 ml trong ngÃ y');

        // Act
        $result = $this->validator->validateLineItem($item);

        // Assert
        $this->assertFalse($result->ok);
        $this->assertSame('EMPTY_CREDENTIALS', $result->code);
    }

    /**
     * Test Case ID: TC_S9_23
     * Description: Từ chối khi liều dùng để trống
     * Scenario Type: Negative
     * Expected Result: code=VALIDATION_FAILURE
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_23
     */
    public function testValidateLineFailsWhenDosageBlank(): void
    {
        // Arrange
        $this->catalog->expects($this->never())->method('findById');
        $item = array('drug_id' => 'X1', 'quantity' => 1, 'dosage' => '');

        // Act
        $result = $this->validator->validateLineItem($item);

        // Assert
        $this->assertFalse($result->ok);
        $this->assertSame('VALIDATION_FAILURE', $result->code);
    }

    /**
     * Test Case ID: TC_S9_24
     * Description: Từ chối khi liều dùng không đạt chính sách định dạng (thiếu bối cảnh sau đơn vị)
     * Scenario Type: Negative
     * Expected Result: code=PASSWORD_POLICY_VIOLATION
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_24
     */
    public function testValidateLineFailsWhenDosageFailsPolicyTooShortDetail(): void
    {
        // Arrange
        $this->catalog->expects($this->never())->method('findById');
        $item = array('drug_id' => 'X1', 'quantity' => 1, 'dosage' => '250mg');

        // Act
        $result = $this->validator->validateLineItem($item);

        // Assert
        $this->assertFalse($result->ok);
        $this->assertSame('PASSWORD_POLICY_VIOLATION', $result->code);
    }

    /**
     * Test Case ID: TC_S9_25
     * Description: Từ chối khi số lượng không phải số nguyên
     * Scenario Type: Negative
     * Expected Result: code=VALIDATION_FAILURE
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_25
     */
    public function testValidateLineFailsWhenQuantityNotInteger(): void
    {
        // Arrange
        $this->catalog->expects($this->never())->method('findById');
        $item = array('drug_id' => 'X1', 'quantity' => 2.5, 'dosage' => '10 ml trong ngÃ y');

        // Act
        $result = $this->validator->validateLineItem($item);

        // Assert
        $this->assertFalse($result->ok);
        $this->assertSame('VALIDATION_FAILURE', $result->code);
    }

    /**
     * Test Case ID: TC_S9_26
     * Description: Từ chối khi số lượng < 1
     * Scenario Type: Negative
     * Expected Result: code=VALIDATION_FAILURE
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_26
     */
    public function testValidateLineFailsWhenQuantityLessThanOne(): void
    {
        // Arrange
        $this->catalog->expects($this->never())->method('findById');
        $item = array('drug_id' => 'X1', 'quantity' => 0, 'dosage' => '10 ml trong ngÃ y');

        // Act
        $result = $this->validator->validateLineItem($item);

        // Assert
        $this->assertFalse($result->ok);
        $this->assertSame('VALIDATION_FAILURE', $result->code);
    }

    /**
     * Test Case ID: TC_S9_27
     * Description: Từ chối khi thuốc không có trong danh mục
     * Scenario Type: Negative
     * Expected Result: code=DRUG_UNKNOWN
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_27
     */
    public function testValidateLineFailsWhenDrugNotInCatalog(): void
    {
        // Arrange
        $this->catalog->method('findById')->with('Nope')->willReturn(null);
        $item = array('drug_id' => 'Nope', 'quantity' => 1, 'dosage' => '10 ml trong ngÃ y');

        // Act
        $result = $this->validator->validateLineItem($item);

        // Assert
        $this->assertFalse($result->ok);
        $this->assertSame('DRUG_UNKNOWN', $result->code);
    }

    /**
     * Test Case ID: TC_S9_28
     * Description: Từ chối khi thuốc không còn hoạt động trong danh mục (ngừng sử dụng)
     * Scenario Type: Negative
     * Expected Result: code=INACTIVE_ACCOUNT
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_28
     */
    public function testValidateLineFailsWhenDrugInactiveInCatalog(): void
    {
        // Arrange
        $this->catalog->method('findById')->willReturn(array(
            'id' => 'Old1',
            'name' => 'Thuá»‘c ngÆ°ng',
            'is_active' => false,
        ));
        $item = array('drug_id' => 'Old1', 'quantity' => 3, 'dosage' => '2 viÃªn sau Äƒn');

        // Act
        $result = $this->validator->validateLineItem($item);

        // Assert
        $this->assertFalse($result->ok);
        $this->assertSame('INACTIVE_ACCOUNT', $result->code);
    }

    /**
     * Test Case ID: TC_S9_29
     * Description: Liều dùng có số thập phân và dấu gạch vẫn hợp lệ khi có mô tả sau đơn vị
     * Scenario Type: Positive
     * Expected Result: ok=true
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_29
     */
    public function testValidateLineAcceptsDecimalDosageWithFrequency(): void
    {
        // Arrange
        $this->catalog->method('findById')->willReturn(array(
            'id' => 'Amp',
            'is_active' => 1,
        ));
        $item = array('drug_id' => 'Amp', 'quantity' => 1, 'dosage' => '12.5 mg/ngÃ y');

        // Act
        $result = $this->validator->validateLineItem($item);

        // Assert
        $this->assertTrue($result->ok);
    }

    /**
     * Test Case ID: TC_S9_30
     * Description: Đơn nhiều dòng hợp lệ được chấp nhận
     * Scenario Type: Positive
     * Expected Result: line_count đúng số dòng
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_30
     */
    public function testValidatePrescriptionAcceptsDistinctLines(): void
    {
        // Arrange
        $this->catalog->method('findById')->willReturnCallback(function ($id) {
            return array('id' => $id, 'is_active' => true);
        });
        $lines = array(
            array('drug_id' => 'A', 'quantity' => 1, 'dosage' => '500 mg/ngÃ y'),
            array('drug_id' => 'B', 'quantity' => 2, 'dosage' => '10 ml sÃ¡ng tá»‘i'),
        );

        // Act
        $result = $this->validator->validatePrescription($lines);

        // Assert
        $this->assertTrue($result->ok);
        $this->assertSame(2, $result->data['line_count']);
    }

    /**
     * Test Case ID: TC_S9_31
     * Description: Đơn rỗng bị từ chối
     * Scenario Type: Negative
     * Expected Result: VALIDATION_FAILURE
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_31
     */
    public function testValidatePrescriptionFailsWhenEmpty(): void
    {
        // Arrange
        $this->catalog->expects($this->never())->method('findById');

        // Act
        $result = $this->validator->validatePrescription(array());

        // Assert
        $this->assertFalse($result->ok);
        $this->assertSame('VALIDATION_FAILURE', $result->code);
    }

    /**
     * Test Case ID: TC_S9_32
     * Description: Trùng cùng mã thuốc trong một đơn bị từ chối (quy tắc trùng nghiệp vụ)
     * Scenario Type: Negative
     * Expected Result: code=DUPLICATED_EMAIL
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_32
     */
    public function testValidatePrescriptionFailsOnDuplicateDrugId(): void
    {
        // Arrange
        $this->catalog->method('findById')->willReturn(array('id' => 'Dup', 'is_active' => true));
        $lines = array(
            array('drug_id' => 'Dup', 'quantity' => 1, 'dosage' => '500 mg/ngÃ y'),
            array('drug_id' => 'Dup', 'quantity' => 2, 'dosage' => '500 mg tá»‘i'),
        );

        // Act
        $result = $this->validator->validatePrescription($lines);

        // Assert
        $this->assertFalse($result->ok);
        $this->assertSame('DUPLICATED_EMAIL', $result->code);
    }

    /**
     * Test Case ID: TC_S9_33
     * Description: Phần tử không phải mảng trong danh sách dòng là lỗi validation
     * Scenario Type: Negative
     * Expected Result: VALIDATION_FAILURE
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_33
     */
    public function testValidatePrescriptionFailsWhenLineNotArray(): void
    {
        // Arrange
        $this->catalog->expects($this->never())->method('findById');

        // Act
        $result = $this->validator->validatePrescription(array(null));

        // Assert
        $this->assertFalse($result->ok);
        $this->assertSame('VALIDATION_FAILURE', $result->code);
    }

    /**
     * Test Case ID: TC_S9_34
     * Description: Từ chối khi quantity không phải là số (kiểu chuỗi chữ)
     * Scenario Type: Negative
     * Expected Result: VALIDATION_FAILURE
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_34
     */
    public function testValidateLineFailsWhenQuantityIsNotNumeric(): void
    {
        // Arrange
        $this->catalog->expects($this->never())->method('findById');
        $item = array('drug_id' => 'X1', 'quantity' => 'abc', 'dosage' => '10 ml trong ngÃ y');

        // Act
        $result = $this->validator->validateLineItem($item);

        // Assert
        $this->assertFalse($result->ok);
        $this->assertSame('VALIDATION_FAILURE', $result->code);
    }

    /**
     * Test Case ID: TC_S9_35
     * Description: Đơn thuốc thất bại nếu một trong các dòng không hợp lệ
     * Scenario Type: Negative
     * Expected Result: Trả về lỗi của dòng tương ứng
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_35
     */
    public function testValidatePrescriptionFailsWhenOneLineIsInvalid(): void
    {
        // Arrange
        $this->catalog->method('findById')->willReturnCallback(function ($id) {
            return array('id' => $id, 'is_active' => true);
        });
        $lines = array(
            array('drug_id' => 'X1', 'quantity' => 1, 'dosage' => '500 mg/ngÃ y'),
            array('drug_id' => 'X2', 'quantity' => -5, 'dosage' => '10 ml sÃ¡ng tá»‘i'), // Lá»—i á»Ÿ quantity
        );

        // Act
        $result = $this->validator->validatePrescription($lines);

        // Assert
        $this->assertFalse($result->ok);
        $this->assertSame('VALIDATION_FAILURE', $result->code);
    }

    /**
     * Test Case ID: TC_S9_42
     * Description: Từ chối khi drug_id không phải kiểu chuỗi
     * Scenario Type: Negative
     * Expected Result: EMPTY_CREDENTIALS
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_42
     */
    public function testValidateLineFailsWhenDrugIdIsNotString(): void
    {
        // Arrange
        $this->catalog->expects($this->never())->method('findById');
        $item = array('drug_id' => 1001, 'quantity' => 1, 'dosage' => '10 ml trong ngÃ y');

        // Act
        $result = $this->validator->validateLineItem($item);

        // Assert
        $this->assertFalse($result->ok);
        $this->assertSame('EMPTY_CREDENTIALS', $result->code);
    }

    /**
     * Test Case ID: TC_S9_43
     * Description: Từ chối khi dosage không phải kiểu chuỗi
     * Scenario Type: Negative
     * Expected Result: VALIDATION_FAILURE
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_43
     */
    public function testValidateLineFailsWhenDosageIsNotString(): void
    {
        // Arrange
        $this->catalog->expects($this->never())->method('findById');
        $item = array('drug_id' => 'X1', 'quantity' => 1, 'dosage' => array('500 mg/ngÃ y'));

        // Act
        $result = $this->validator->validateLineItem($item);

        // Assert
        $this->assertFalse($result->ok);
        $this->assertSame('VALIDATION_FAILURE', $result->code);
    }

    /**
     * Test Case ID: TC_S9_44
     * Description: Từ chối khi thiếu quantity trong item
     * Scenario Type: Negative
     * Expected Result: VALIDATION_FAILURE
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_44
     */
    public function testValidateLineFailsWhenQuantityMissing(): void
    {
        // Arrange
        $this->catalog->expects($this->never())->method('findById');
        $item = array('drug_id' => 'X1', 'dosage' => '10 ml trong ngÃ y');

        // Act
        $result = $this->validator->validateLineItem($item);

        // Assert
        $this->assertFalse($result->ok);
        $this->assertSame('VALIDATION_FAILURE', $result->code);
    }

    /**
     * Test Case ID: TC_S9_45
     * Description: Từ chối khi catalog trả dữ liệu thuốc thiếu trường id
     * Scenario Type: Negative
     * Expected Result: DRUG_UNKNOWN
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_45
     */
    public function testValidateLineFailsWhenCatalogEntryMissingId(): void
    {
        // Arrange
        $this->catalog->method('findById')->willReturn(array(
            'name' => 'Broken Drug',
            'is_active' => true,
        ));
        $item = array('drug_id' => 'Broken1', 'quantity' => 1, 'dosage' => '250 mg/ngÃ y');

        // Act
        $result = $this->validator->validateLineItem($item);

        // Assert
        $this->assertFalse($result->ok);
        $this->assertSame('DRUG_UNKNOWN', $result->code);
    }

    /**
     * Test Case ID: TC_S9_46
     * Description: Từ chối khi thuốc có is_active = "false" trong catalog
     * Scenario Type: Negative
     * Expected Result: INACTIVE_ACCOUNT
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_46
     */
    public function testValidateLineFailsWhenCatalogActiveFlagIsFalseString(): void
    {
        // Arrange
        $this->catalog->method('findById')->willReturn(array(
            'id' => 'OldX',
            'name' => 'Thuoc ngung',
            'is_active' => 'false',
        ));
        $item = array('drug_id' => 'OldX', 'quantity' => 2, 'dosage' => '500 mg/ngÃ y');

        // Act
        $result = $this->validator->validateLineItem($item);

        // Assert
        $this->assertFalse($result->ok);
        $this->assertSame('INACTIVE_ACCOUNT', $result->code);
    }

    /**
     * Test Case ID: TC_S9_47
     * Description: Trùng mã thuốc sau khi trim khoảng trắng vẫn bị từ chối
     * Scenario Type: Negative
     * Expected Result: DUPLICATED_EMAIL
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_47
     */
    public function testValidatePrescriptionFailsOnDuplicateDrugIdAfterTrim(): void
    {
        // Arrange
        $this->catalog->method('findById')->willReturn(array('id' => 'Dup', 'is_active' => true));
        $lines = array(
            array('drug_id' => 'Dup', 'quantity' => 1, 'dosage' => '500 mg/ngÃ y'),
            array('drug_id' => ' Dup ', 'quantity' => 2, 'dosage' => '10 ml sÃ¡ng tá»‘i'),
        );

        // Act
        $result = $this->validator->validatePrescription($lines);

        // Assert
        $this->assertFalse($result->ok);
        $this->assertSame('DUPLICATED_EMAIL', $result->code);
    }

    /**
     * Test Case ID: TC_S9_48
     * Description: Quantity dạng scientific notation không được xem là số nguyên hợp lệ
     * Scenario Type: Negative
     * Expected Result: VALIDATION_FAILURE
     * Related Module: Drug & Notification
     * Link Anchor: #TC_S9_48
     */
    public function testGapQuantityScientificNotationShouldBeRejected(): void
    {
        // Arrange
        $this->catalog->expects($this->never())->method('findById');
        $item = array('drug_id' => 'X1', 'quantity' => '1e2', 'dosage' => '10 ml trong ngÃ y');

        // Act
        $result = $this->validator->validateLineItem($item);

        // Assert
        $this->assertFalse($result->ok);
        $this->assertSame('VALIDATION_FAILURE', $result->code);
    }
}



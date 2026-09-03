<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;

class PermissionHelper
{
    protected static $maps = [
        'view-dashboard' => ['th' => 'ดูแดชบอร์ด', 'en' => 'View Dashboard', 'zh' => '查看仪表板'],
        'view-notifications' => ['th' => 'ดูการแจ้งเตือน', 'en' => 'View Notifications', 'zh' => '查看通知'],
        'manage-users' => ['th' => 'จัดการผู้ใช้งาน', 'en' => 'Manage Users', 'zh' => '管理用户'],
        'manage-roles' => ['th' => 'จัดการบทบาท', 'en' => 'Manage Roles', 'zh' => '管理角色'],
        'manage-settings' => ['th' => 'จัดการการตั้งค่า', 'en' => 'Manage Settings', 'zh' => '管理设置'],
        'view-trash' => ['th' => 'ดูถังขยะกลาง', 'en' => 'View Trash', 'zh' => '查看回收站'],
        'view-employers' => ['th' => 'ดูข้อมูลนายจ้าง', 'en' => 'View Employers', 'zh' => '查看雇主'],
        'create-employers' => ['th' => 'สร้างนายจ้าง', 'en' => 'Create Employers', 'zh' => '创建雇主'],
        'edit-employers' => ['th' => 'แก้ไขนายจ้าง', 'en' => 'Edit Employers', 'zh' => '编辑雇主'],
        'delete-employers' => ['th' => 'ลบนายจ้าง', 'en' => 'Delete Employers', 'zh' => '删除雇主'],
        'restore-employers' => ['th' => 'กู้คืนนายจ้าง', 'en' => 'Restore Employers', 'zh' => '恢复雇主'],
        'force-delete-employers' => ['th' => 'ลบนายจ้างถาวร', 'en' => 'Force Delete Employers', 'zh' => '强制删除雇主'],
        'view-employees' => ['th' => 'ดูข้อมูลลูกจ้าง', 'en' => 'View Employees', 'zh' => '查看员工'],
        'create-employees' => ['th' => 'สร้างลูกจ้าง', 'en' => 'Create Employees', 'zh' => '创建员工'],
        'edit-employees' => ['th' => 'แก้ไขลูกจ้าง', 'en' => 'Edit Employees', 'zh' => '编辑员工'],
        'update-progress-steps' => ['th' => 'ติ๊กความคืบหน้าขั้นตอน (Pre-Production/Workflow/มติลงทะเบียน/มติต่ออายุ)', 'en' => 'Update Progress Steps', 'zh' => '更新进度步骤'],
        'delete-employees' => ['th' => 'ลบลูกจ้าง (ลงถังขยะ)', 'en' => 'Delete Employees', 'zh' => '删除员工'],
        'terminate-employees' => ['th' => 'แจ้งออกลูกจ้าง', 'en' => 'Terminate Employees', 'zh' => '解雇员工'],
        'restore-employees' => ['th' => 'กู้คืนลูกจ้าง', 'en' => 'Restore Employees', 'zh' => '恢复员工'],
        'force-delete-employees' => ['th' => 'ลบลูกจ้างถาวร', 'en' => 'Force Delete Employees', 'zh' => '强制删除员工'],
        'view-agents' => ['th' => 'ดูข้อมูลเอเจนซี่', 'en' => 'View Agents', 'zh' => '查看中介'],
        'create-agents' => ['th' => 'สร้างเอเจนซี่', 'en' => 'Create Agents', 'zh' => '创建中介'],
        'edit-agents' => ['th' => 'แก้ไขเอเจนซี่', 'en' => 'Edit Agents', 'zh' => '编辑中介'],
        'delete-agents' => ['th' => 'ลบเอเจนซี่', 'en' => 'Delete Agents', 'zh' => '删除中介'],
        'restore-agents' => ['th' => 'กู้คืนเอเจนซี่', 'en' => 'Restore Agents', 'zh' => '恢复中介'],
        'force-delete-agents' => ['th' => 'ลบเอเจนซี่ถาวร', 'en' => 'Force Delete Agents', 'zh' => '强制删除中介'],
        'view-importers' => ['th' => 'ดูข้อมูลผู้นำเข้า', 'en' => 'View Importers', 'zh' => '查看进口商'],
        'create-importers' => ['th' => 'สร้างผู้นำเข้า', 'en' => 'Create Importers', 'zh' => '创建进口商'],
        'edit-importers' => ['th' => 'แก้ไขผู้นำเข้า', 'en' => 'Edit Importers', 'zh' => '编辑进口商'],
        'delete-importers' => ['th' => 'ลบผู้นำเข้า', 'en' => 'Delete Importers', 'zh' => '删除进口商'],
        'restore-importers' => ['th' => 'กู้คืนผู้นำเข้า', 'en' => 'Restore Importers', 'zh' => '恢复进口商'],
        'force-delete-importers' => ['th' => 'ลบผู้นำเข้าถาวร', 'en' => 'Force Delete Importers', 'zh' => '强制删除进口商'],
        'view-delegates' => ['th' => 'ดูข้อมูลผู้รับมอบ', 'en' => 'View Delegates', 'zh' => '查看代表'],
        'create-delegates' => ['th' => 'สร้างผู้รับมอบ', 'en' => 'Create Delegates', 'zh' => '创建代表'],
        'edit-delegates' => ['th' => 'แก้ไขผู้รับมอบ', 'en' => 'Edit Delegates', 'zh' => '编辑代表'],
        'delete-delegates' => ['th' => 'ลบผู้รับมอบ', 'en' => 'Delete Delegates', 'zh' => '删除代表'],
        'restore-delegates' => ['th' => 'กู้คืนผู้รับมอบ', 'en' => 'Restore Delegates', 'zh' => '恢复代表'],
        'force-delete-delegates' => ['th' => 'ลบผู้รับมอบถาวร', 'en' => 'Force Delete Delegates', 'zh' => '强制删除代表'],
        'view-addresses' => ['th' => 'ดูข้อมูลที่อยู่', 'en' => 'View Addresses', 'zh' => '查看地址'],
        'create-addresses' => ['th' => 'สร้างที่อยู่', 'en' => 'Create Addresses', 'zh' => '创建地址'],
        'edit-addresses' => ['th' => 'แก้ไขที่อยู่', 'en' => 'Edit Addresses', 'zh' => '编辑地址'],
        'delete-addresses' => ['th' => 'ลบที่อยู่', 'en' => 'Delete Addresses', 'zh' => '删除地址'],
        'restore-addresses' => ['th' => 'กู้คืนที่อยู่', 'en' => 'Restore Addresses', 'zh' => '恢复地址'],
        'force-delete-addresses' => ['th' => 'ลบที่อยู่ถาวร', 'en' => 'Force Delete Addresses', 'zh' => '强制删除地址'],
        'manage-tickets' => ['th' => 'จัดการตั๋วงาน (Job Tickets)', 'en' => 'Manage Job Tickets', 'zh' => '管理工单'],
        'cancel-notifications' => ['th' => 'ยกเลิกการแจ้งเตือน', 'en' => 'Cancel Notifications', 'zh' => '取消通知'],
        'renew-notifications' => ['th' => 'ต่ออายุจากแจ้งเตือน', 'en' => 'Renew via Notification', 'zh' => '通过通知续订'],
        'restore-notifications' => ['th' => 'กู้คืนการแจ้งเตือน', 'en' => 'Restore Notifications', 'zh' => '恢复通知'],
        'force-delete-notifications' => ['th' => 'ลบการแจ้งเตือนถาวร', 'en' => 'Force Delete Notifications', 'zh' => '强制删除通知'],
        'use-chat' => ['th' => 'ใช้งานแชท', 'en' => 'Use Chat', 'zh' => '使用聊天'],
        'view-finance' => ['th' => 'ดูข้อมูลการเงิน', 'en' => 'View Finance', 'zh' => '查看财务'],
        'manage-finance' => ['th' => 'จัดการการเงิน', 'en' => 'Manage Finance', 'zh' => '管理财务'],
        'view-pdf-templates' => ['th' => 'ดูเทมเพลต PDF', 'en' => 'View PDF Templates', 'zh' => '查看 PDF 模板'],
        'create-pdf-templates' => ['th' => 'สร้างเทมเพลต PDF', 'en' => 'Create PDF Templates', 'zh' => '创建 PDF 模板'],
        'edit-pdf-templates' => ['th' => 'แก้ไขเทมเพลต PDF', 'en' => 'Edit PDF Templates', 'zh' => '编辑 PDF 模板'],
        'delete-pdf-templates' => ['th' => 'ลบเทมเพลต PDF', 'en' => 'Delete PDF Templates', 'zh' => '删除 PDF 模板'],
        'view-labor-ledger' => ['th' => 'ดูสมุดบัญชี Pro Walker Labor', 'en' => 'View Labor Ledger', 'zh' => '查看劳工账簿'],
        'manage-labor-ledger' => ['th' => 'จัดการสมุดบัญชี Pro Walker Labor', 'en' => 'Manage Labor Ledger', 'zh' => '管理劳工账簿'],
        'approve-production' => ['th' => 'อนุมัติงาน Pre-Production', 'en' => 'Approve Production', 'zh' => '批准生产'],
        'manage-own-workflow' => ['th' => 'จัดการ Workflow ของตนเอง', 'en' => 'Manage Own Workflow', 'zh' => '管理自己的工作流程'],
    ];

    public static function getLabel($permissionName)
    {
        $locale = App::getLocale();

        if (isset(self::$maps[$permissionName])) {
            return self::$maps[$permissionName][$locale] ?? self::$maps[$permissionName]['en'];
        }

        return ucwords(str_replace('-', ' ', $permissionName));
    }
}

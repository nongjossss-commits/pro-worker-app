<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ประวัติการจ้างงานทั้งหมด') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-4">
                        <h3 class="text-lg font-medium text-gray-900">รายการลูกจ้างที่สิ้นสุดการจ้างงาน</h3>
                        <p class="text-sm text-gray-600">แสดงรายชื่อลูกจ้างทั้งหมดที่ถูกยกเลิกการจ้างงานในระบบ</p>
                    </div>

                    <!-- Employee Table -->
                    <div class="overflow-x-auto bg-white rounded-lg shadow">
                        <table class="min-w-full leading-normal">
                            <thead>
                                <tr class="border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <th class="px-5 py-3">รูปถ่าย</th>
                                    <th class="px-5 py-3">ชื่อ (ไทย/อังกฤษ)</th>
                                    <th class="px-5 py-3">หนังสือเดินทาง</th>
                                    <th class="px-5 py-3">สัญชาติ</th>
                                    <th class="px-5 py-3">นายจ้างล่าสุด</th>
                                    <th class="px-5 py-3">วันที่สิ้นสุดการจ้าง</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($terminatedEmployees as $employee)
                                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                                        <td class="px-5 py-5 text-sm">
                                            <div class="flex-shrink-0 w-12 h-12">
                                                <img class="w-full h-full rounded-full"
                                                     src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : asset('images/default-user.png') }}"
                                                     alt="Employee Photo">
                                            </div>
                                        </td>
                                        <td class="px-5 py-5 text-sm">
                                            <p class="text-gray-900 whitespace-no-wrap">{{ $employee->employeeNameTh ?? '-' }}</p>
                                            <p class="text-gray-600 whitespace-no-wrap">{{ $employee->employeeNameEn ?? '-' }}</p>
                                        </td>
                                        <td class="px-5 py-5 text-sm">
                                            <p class="text-gray-900 whitespace-no-wrap">{{ $employee->employeePassport ?? '-' }}</p>
                                        </td>
                                        <td class="px-5 py-5 text-sm">
                                            <p class="text-gray-900 whitespace-no-wrap">{{ $employee->employeeNationality ?? '-' }}</p>
                                        </td>
                                        <td class="px-5 py-5 text-sm">
                                            <p class="text-gray-900 whitespace-no-wrap">{{ $employee->employer->employerNameTh ?? 'N/A' }}</p>
                                        </td>
                                        <td class="px-5 py-5 text-sm">
                                            <p class="text-gray-900 whitespace-no-wrap">{{ $employee->termination_date ? \Carbon\Carbon::parse($employee->termination_date)->format('d M Y') : 'N/A' }}</p>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-10 px-5 text-sm text-gray-500">
                                            ไม่พบข้อมูลลูกจ้างที่สิ้นสุดการจ้างงาน
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $terminatedEmployees->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
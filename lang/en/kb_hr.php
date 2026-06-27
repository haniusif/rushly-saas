<?php

return [
    'label'    => 'HR',
    'overview' => 'Internal staff: users & roles, payroll generation, and asset assignments.',
    'sub_pages' => [

        'users-roles' => [
            'icon'    => 'UserCog',
            'label'   => 'Users & Roles',
            'purpose' => 'Manage internal staff accounts and assign permission-based roles. The system separates SUPER_ADMIN (global) from ADMIN (company-scoped) and other user types (merchants, delivery personnel, hub staff).',
            'pages' => [
                ['path' => 'Users Index',          'desc' => 'Paginated company staff (name, email, mobile, hub, role, monthly salary, status) with name/email/phone filters. Owner accounts show a lock indicator and cannot be deleted.'],
                ['path' => 'Users Create / Edit',  'desc' => 'Form for name, email, password (optional on edit), phone, NID, joining date, designation, department, hub assignment, initial salary, and profile image upload.'],
                ['path' => 'User Permissions',     'desc' => 'Per-user override on top of role permissions. Gated behind permission_update.'],
                ['path' => 'Roles Index',          'desc' => 'List of custom roles with name, slug, permission count and active status.'],
                ['path' => 'Roles Create / Edit',  'desc' => 'Define a role by checking granular permissions (user_read, role_update, etc.) grouped by module.'],
            ],
            'fields' => [
                'name', 'email', 'mobile', 'nid_number', 'designation_id', 'department_id',
                'hub_id', 'role_id', 'salary', 'status', 'image', 'permissions',
                'user_type', 'is_locked', 'joining_date', 'address',
            ],
            'status_flow' => [
                ['label' => 'Active',   'tone' => 'ok'],
                ['label' => 'Inactive', 'tone' => 'warn'],
            ],
            'cross_links' => 'Users bind to Hubs, Departments and Designations. Roles drive Payroll authorisation. Permission overrides let you grant or revoke access without reassigning a role.',
            'notes'       => 'Multi-tenant: non-superadmins only ever see their own company_id-scoped users. ID=1 and company_owner=yes accounts are locked from deletion. Passwords hashed via Hash::make(). Filter search is server-side LIKE on name / email / mobile. Role permissions stored as a JSON array.',
        ],

        'payroll' => [
            'icon'    => 'Briefcase',
            'label'   => 'Payroll',
            'purpose' => 'Record manual salary payments to staff and track payment history per month. Single-payment processing (not bulk generation), with account-source validation and balance deduction.',
            'pages' => [
                ['path' => 'Salary Index',    'desc' => 'List of paid salaries with user, email, source account (formatted per gateway — Cash, bKash, Rocket, Nagad, or bank), month, payment date, note and amount. Filters: user, month.'],
                ['path' => 'Salary Create',   'desc' => 'Pick staff user, month, date, payment account (admin-type accounts only), amount validated against the account balance, optional note.'],
                ['path' => 'Salary Edit',     'desc' => 'Modify a payment record. Validates: current_balance + refunded_amount ≥ new_amount.'],
                ['path' => 'Salary Pay Slip', 'desc' => 'Printable pay slip for a single salary entry, using the SalaryGenerate row for that user / month.'],
            ],
            'fields' => [
                'user_id', 'month', 'account_id', 'amount', 'date', 'note',
                'company_id', 'account_holder_name', 'account_no',
            ],
            'cross_links' => 'Salary links to SalaryGenerate (the monthly target) and to Account (payment source with a running balance). The account balance is decremented on store and recalculated on save. Autocomplete uses the User lookup.',
            'notes'       => 'Insufficient balance triggers a warning toast — the payment is blocked. Amount references SalaryGenerate.amount for that month/user combo. Account dropdown excludes merchant-type accounts. Date defaults to today, month to current YYYY-MM.',
        ],

        'assets' => [
            'icon'    => 'HardDrive',
            'label'   => 'Assets',
            'purpose' => 'Track company property assigned to hubs (laptops, scanners, vehicles). Records supplier, quantity, warranty, invoice, and acquisition cost.',
            'pages' => [
                ['path' => 'Asset Index',  'desc' => 'All assets sorted by name. Columns: name, category, hub, supplier, quantity, warranty, invoice number, amount. Actions: edit, delete.'],
                ['path' => 'Asset Create', 'desc' => 'Add a new asset: name, category, hub, supplier name, quantity, warranty period, invoice number, amount (required), description.'],
                ['path' => 'Asset Edit',   'desc' => 'Modify asset details; form pre-populates from the existing entity.'],
            ],
            'fields' => [
                'name', 'assetcategory_id', 'hub_id', 'supplyer_name', 'quantity',
                'warranty', 'invoice_no', 'amount', 'description', 'author', 'company_id',
            ],
            'cross_links' => 'Assets are scoped to a Hub. Category lookup uses the AssetCategory table. Author auto-populates with the authenticated user on create/update.',
            'notes'       => 'Companywise scoping enforces multi-tenant isolation. Quantity is optional. Warranty / invoice fields are free-form text. Amount supports two decimal places. All create/update actions are logged via Spatie ActivityLog.',
        ],

    ],
];

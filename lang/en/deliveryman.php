<?php

return array (
  // existing
  'title'              => 'Couriers',
  'create_deliveryman' => 'Create Courier',
  'edit_deliveryman'   => 'Edit Courier',
  'added_msg'          => 'Couriers successfully added.',
  'update_msg'         => 'Couriers successfully update.',
  'delete_msg'         => 'Couriers successfully deleted.',
  'error_msg'          => 'Something went wrong.',

  // Section headers
  'section_basic'      => 'Basic information',
  'section_id'         => 'Identity',
  'section_address'    => 'Address',
  'section_employment' => 'Employment',
  'section_license'    => 'License',
  'section_bank'       => 'Banking (Freelancer)',
  'section_documents'  => 'Official documents',

  // Basic identity
  'full_name'          => 'Full name',
  'name_en'            => 'Name in English',
  'mobile'             => 'Mobile',
  'alt_mobile'         => 'Alternate mobile',
  'email'              => 'Email',
  'password'           => 'Password',
  'gender'             => 'Gender',
  'gender_male'        => 'Male',
  'gender_female'      => 'Female',
  'dob'                => 'Date of birth',
  'nationality'        => 'Nationality',
  'nationality_empty'  => 'Nationalities table is empty — run NationalitySeeder.',

  // Identity / ID
  'id_type'            => 'ID type',
  'id_type_national'   => 'National ID',
  'id_type_iqama'      => 'Iqama',
  'id_number'          => 'ID number',
  'id_expiry'          => 'Expiry date',
  'id_image'           => 'ID photo',
  'file_help'          => 'JPEG/PNG, max 5 MB',

  // Address
  'address'                            => 'Detailed address',
  'district'                           => 'District',
  'short_national_address'             => 'Short national address code',
  'short_national_address_placeholder' => 'e.g. ABCD1234',

  // Employment
  'driver_type'                   => 'Courier type',
  'driver_type_freelancer'        => 'Freelancer',
  'driver_type_outsourced'        => 'Outsourced',
  'driver_type_company_courier'   => 'Company Courier',
  'employee_number'               => 'Employee number',
  'supplier_company'              => 'Supplier company',
  'supplier_company_empty'        => 'No supplier companies registered yet.',
  'joining_date'                  => 'Joining date',
  'contract_end_date'             => 'Contract end date',
  'contract_expiry_hint'          => 'Warning is surfaced when contract ends within 30 days.',
  'status'                        => 'Status',
  'status_active'                 => 'Active',
  'status_suspended'              => 'Suspended',
  'status_leave'                  => 'On leave',
  'status_terminated'             => 'Contract ended',
  'hub'                           => 'Branch / Hub',
  'direct_manager'                => 'Direct manager',
  'operational_area'              => 'Operational area',
  'salary'                        => 'Salary',

  // License + iqama
  'license_number'                => 'License number',
  'license_expiry'                => 'License expiry',
  'iqama_expiry'                  => 'Iqama expiry',

  // Bank
  'bank_account_no'               => 'Bank account number',
  'iban'                          => 'IBAN',
  'iban_placeholder'              => 'SA00 0000 0000 0000 0000 0000',

  // Documents
  'personal_photo'                => 'Personal photo',
  'license_photo'                 => 'License photo',
  'iqama_photo'                   => 'Iqama photo',
  'contract_photo'                => 'Contract photo',
  'promissory_note_photo'         => 'Promissory note photo',

  // Wizard navigation
  'wizard_next'                   => 'Next',
  'wizard_prev'                   => 'Previous',
  'wizard_submit'                 => 'Submit',
  'wizard_step_of'                => 'Step :current of :total',
);

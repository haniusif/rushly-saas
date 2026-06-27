<?php

return [
    'label'    => 'Site CMS',
    'overview' => 'Public-facing site content: blogs, pages, services, FAQs, partners and other marketing-site sections.',
    'sub_pages' => [

        'front-web' => [
            'icon'    => 'Layout',
            'label'   => 'Front Web',
            'purpose' => 'Unified CMS for the public marketing site — blogs, service listings, FAQs, team / partners, feature highlights, sections (banner / achievements / about) and social-media links. Controls what appears on the customer-facing website.',
            'pages' => [
                ['path' => 'Blogs',                              'desc' => 'Create, edit and manage blog articles: title, rich-text body, featured image, display order and active/inactive status.'],
                ['path' => 'Pages',                              'desc' => 'Edit predefined content pages (About Us, Privacy & Policy, Terms, FAQ page) with title, description and status.'],
                ['path' => 'Services',                           'desc' => 'Curate service offerings — title, description, icon/image, position and visibility for the public service directory.'],
                ['path' => 'FAQ',                                'desc' => 'Maintain question/answer pairs with ordering and publication status.'],
                ['path' => 'Partners',                           'desc' => 'Partner logos and external links — image, URL, order, status.'],
                ['path' => 'Sections / Why Courier / Socials',   'desc' => 'Hero banners, value-proposition blocks (Why Choose Us / Why Courier) and social-media links (Facebook, Twitter, Instagram) with icons and URLs.'],
            ],
            'fields' => [
                'title', 'question', 'description', 'answer',
                'image', 'icon', 'url',
                'position', 'status', 'company_id',
                'created_by', 'created_at', 'updated_at',
            ],
            'status_flow' => [
                ['label' => 'Active (1)',   'tone' => 'ok'],
                ['label' => 'Inactive (0)', 'tone' => 'bad'],
            ],
            'cross_links' => 'Front-end reads from these tables via FrontWeb repositories. Public routes /faq-list, /get-blogs, /service-details/{id} pull active records. Images are stored in the uploads table and served from public/uploads/{type}/. Per-tenant isolation via company_id.',
            'notes'       => 'companyWise() scope used across all content types. Blogs track created_by (publishing user). position controls display order (lower = first). Images live in subfolders per content type (uploads/blogs/, uploads/services/, …). FAQ and Pages are content-only (no images). Slugs are not used — pages identified by page name (e.g. faq, privacy). Draft states managed by the status flag alone (no separate draft table).',
        ],

    ],
];

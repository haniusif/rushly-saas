@extends('backend.partials.master')
@section('title')
    {{ __('levels.blogs') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <span class="tw-text-gray-700">{{ __('levels.front_web') }}</span>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('levels.blogs') }}</span>
            </nav>

            <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                <div class="tw-flex tw-items-center tw-justify-between tw-gap-3 tw-px-5 tw-py-4 tw-border-b tw-border-gray-100">
                    <div>
                        <h1 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('levels.blogs') }}</h1>
                        <p class="tw-text-xs tw-text-gray-500 tw-mt-1 tw-m-0">{{ @$blogs->total() ?? count($blogs) }} {{ __('Showing') }}</p>
                    </div>
                    @if (hasPermission('blogs_create'))
                        <a href="{{ route('blogs.create') }}"
                           class="tw-inline-flex tw-items-center tw-gap-2 tw-h-10 tw-px-4 tw-text-sm tw-font-medium tw-text-white tw-bg-brand-600 hover:tw-bg-brand-700 tw-rounded-lg tw-no-underline">
                            <i class="fa fa-plus tw-text-xs"></i> {{ __('levels.add') }}
                        </a>
                    @endif
                </div>

                <div class="tw-overflow-x-auto">
                    <table class="tw-w-full tw-text-sm">
                        <thead class="tw-bg-gray-50">
                            <tr class="tw-text-left tw-text-xs tw-uppercase tw-tracking-wider tw-text-gray-500">
                                <th class="tw-px-5 tw-py-3 tw-font-medium tw-w-12">#</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.title') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.description') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.created_by') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.date') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.status') }}</th>
                                @if (hasPermission('blogs_update') || hasPermission('blogs_delete'))
                                    <th class="tw-px-5 tw-py-3 tw-font-medium tw-text-right">{{ __('levels.actions') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="tw-divide-y tw-divide-gray-100">
                            @php $i = method_exists($blogs, 'firstItem') ? $blogs->firstItem() : 1; @endphp
                            @forelse ($blogs as $blog)
                                <tr class="hover:tw-bg-gray-50 tw-transition-colors">
                                    <td class="tw-px-5 tw-py-3 tw-text-gray-500 tw-tabular-nums tw-align-top">{{ $i++ }}</td>
                                    <td class="tw-px-5 tw-py-3 tw-align-top">
                                        <div class="tw-flex tw-items-center tw-gap-3 tw-min-w-0">
                                            <img src="{{ @$blog->image }}" alt="" class="tw-w-12 tw-h-12 tw-rounded-lg tw-object-cover tw-bg-gray-100 tw-shrink-0" />
                                            <div class="tw-min-w-0">
                                                <div class="tw-font-medium tw-text-gray-900 tw-line-clamp-1">{{ @$blog->title }}</div>
                                                <div class="tw-text-xs tw-text-gray-400 tw-mt-0.5">{{ __('levels.position') }}: {{ @$blog->position }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="tw-px-5 tw-py-3 tw-text-gray-700 tw-align-top tw-max-w-md">
                                        <div class="tw-line-clamp-2 tw-text-xs tw-text-gray-600">{!! strip_tags(@$blog->description) !!}</div>
                                    </td>
                                    <td class="tw-px-5 tw-py-3 tw-text-gray-700 tw-align-top">{{ @$blog->user->name }}</td>
                                    <td class="tw-px-5 tw-py-3 tw-text-xs tw-text-gray-500 tw-tabular-nums tw-align-top">{{ dateFormat($blog->created_at) }}</td>
                                    <td class="tw-px-5 tw-py-3 tw-align-top">{!! @$blog->my_status !!}</td>
                                    @if (hasPermission('blogs_update') || hasPermission('blogs_delete'))
                                        <td class="tw-px-5 tw-py-3 tw-text-right tw-align-top">
                                            <div class="dropdown tw-inline-block">
                                                <button tabindex="-1" data-toggle="dropdown" type="button"
                                                        class="tw-inline-flex tw-items-center tw-justify-center tw-w-9 tw-h-9 tw-rounded-lg hover:tw-bg-gray-100 tw-text-gray-600 tw-border-0 tw-bg-transparent">
                                                    <i class="fa fa-ellipsis-h"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    @if (hasPermission('blogs_update'))
                                                        <a href="{{ route('blogs.edit', $blog->id) }}" class="dropdown-item">
                                                            <i class="fas fa-edit"></i> {{ __('levels.edit') }}
                                                        </a>
                                                    @endif
                                                    @if (hasPermission('blogs_delete'))
                                                        <form id="delete" action="{{ route('blogs.delete', $blog->id) }}" method="POST" data-title="{{ __('Do you want to delete blog ?') }}">
                                                            @method('DELETE')
                                                            @csrf
                                                            <button type="submit" class="dropdown-item tw-text-red-600">
                                                                <i class="fa fa-trash"></i> {{ __('levels.delete') }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="7" class="tw-px-5 tw-py-12 tw-text-center">
                                    <div class="tw-text-gray-300 tw-mb-2"><i class="fa fa-newspaper-o tw-text-3xl"></i></div>
                                    <p class="tw-text-sm tw-text-gray-500 tw-m-0">No blog posts yet.</p>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($blogs, 'hasPages') && $blogs->hasPages())
                    <div class="tw-flex tw-flex-col sm:tw-flex-row sm:tw-items-center sm:tw-justify-between tw-gap-3 tw-px-5 tw-py-4 tw-border-t tw-border-gray-100">
                        <p class="tw-text-xs tw-text-gray-500 tw-m-0">
                            {!! __('Showing') !!} <span class="tw-font-medium tw-text-gray-700">{{ $blogs->firstItem() }}</span>
                            {!! __('to') !!} <span class="tw-font-medium tw-text-gray-700">{{ $blogs->lastItem() }}</span>
                            {!! __('of') !!} <span class="tw-font-medium tw-text-gray-700">{{ $blogs->total() }}</span>
                            {!! __('results') !!}
                        </p>
                        <div>{{ $blogs->links() }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

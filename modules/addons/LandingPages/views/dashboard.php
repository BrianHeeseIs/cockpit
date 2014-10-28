@if(count($landingpages))

    <div class="uk-margin-bottom">
        <span class="uk-button-group">
            @hasaccess?("LandingPages", 'manage.landingpages')
            <a class="uk-button uk-button-success uk-button-small" href="@route('/landingpages/landingpage')" title="@lang('Add landingpage')" data-uk-tooltip="{pos:'bottom'}"><i class="uk-icon-plus-circle"></i></a>
            @end
            <a class="uk-button app-button-secondary uk-button-small" href="@route('/landingpages')" title="@lang('Show all landingpages')" data-uk-tooltip="{pos:'bottom'}"><i class="uk-icon-ellipsis-h"></i></a>
        </span>
    </div>


    <span class="uk-text-small uk-text-uppercase uk-text-muted">@lang('Latest')</span>
    <ul class="uk-list uk-list-space">
        @foreach($landingpages as $landingpage)
        <li><a href="@route('/landingpages/entries/'.$landingpage['_id'])"><i class="uk-icon-map-marker"></i> {{ $landingpage["name"] }}</a></li>
        @endforeach
    </ul>

@else

    <div class="uk-text-center">
        <h2><i class="uk-icon-list"></i></h2>
        <p class="uk-text-muted">
            @lang('You don\'t have any landingpages created.')
        </p>
        @hasaccess?("LandingPages", 'manage.landingpages')
        <a href="@route('/landingpages/landingpage')" class="uk-button uk-button-success" title="@lang('Create a landingpage')" data-uk-tooltip="{pos:'bottom'}"><i class="uk-icon-plus-circle"></i></a>
        @end
    </div>

@endif
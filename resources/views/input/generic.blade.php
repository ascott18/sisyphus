<div class="form-group">
    <?php
        // Convert dot notation to array indexer notation
        $nameIndexer = preg_replace('/\.([^\.]+)/', '[$1]', $name);
        $nameIndexerEscaped = addslashes($nameIndexer);
        if (!isset($type)) $type = 'text';

        // Dont use real hidden inputs - they cant be validated by angular.
        $hidden = false;
        if ($type == 'hidden') {$type = 'text'; $hidden = true;}
    ?>
    @if(!$hidden && $label)<label for="{{$name}}">{{ $label }}</label>
    @endif
    <input type="{{$type}}"
           @if ($hidden) style="display: none" @endif
           class="form-control"
           name="{{ $nameIndexer }}"
           id="{{ $name }}"
           @if ($label) placeholder="{{ $label }}" @endif
           {{$type == 'hidden' ? 'ng-value' : 'ng-model'}}="{{ $name }}"
            @foreach($attrs as $attr => $value)
                {{$attr}}="{{$value}}"
            @endforeach
    >

    <div ng-cloak ng-show="form.$submitted || form['{{$nameIndexerEscaped}}'].$touched">
        <span class="text-danger" ng-show="form['{{$nameIndexerEscaped}}'].$error.required">{{$label}} is required.</span>
        <span class="text-danger" ng-show="form['{{$nameIndexerEscaped}}'].$error.pattern">{{$label}} {{ isset($pattern) ? $pattern : 'is invalid'}}.</span>
        @if ($type == 'email')
            <span class="text-danger" ng-show="form['{{$nameIndexerEscaped}}'].$error.email">{{$label}} is not a valid email address.</span>
        @endif
    </div>
</div>
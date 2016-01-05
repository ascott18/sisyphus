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
    @if(!$hidden)<label for="{{$name}}">{{ $label }}</label>
    @endif
    <input type="{{$type}}"
           @if ($hidden) style="display: none" @endif
           class="form-control"
           name="{{ $nameIndexer }}"
           id="{{ $name }}"
           placeholder="{{ $label }}"
           {{$type == 'hidden' ? 'ng-value' : 'ng-model'}}="{{ $name }}"
            @foreach($attrs as $attr => $value)
                {{$attr}}="{{$value}}"
            @endforeach
    >

    <div ng-show="form.$submitted || form['{{$nameIndexerEscaped}}'].$touched">
        <span class="text-danger" ng-show="form['{{$nameIndexerEscaped}}'].$error.required">{{$label}} is required.</span>
        <span class="text-danger" ng-show="form['{{$nameIndexerEscaped}}'].$error.pattern">{{$label}} {{ isset($pattern) ? $pattern : 'is invalid'}}.</span>
    </div>
</div>
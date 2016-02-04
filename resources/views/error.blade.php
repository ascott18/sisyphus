@extends('layouts.master', [
    'breadcrumbs' => [
        ['Error'],
        [$response['statusName'], '.'],
    ]
])


@section('content')
    <style>

        .error-page {
            text-align: center;
            vertical-align: middle
        }
        .error-page h2 {
            font-size: 9em
        }

        .exception{
            text-align: left;
        }
        
        .exception td{
            /*font-family: monospace;*/
        }

        .broken {
            -webkit-transform: rotate(-11deg);
            -moz-transform:    rotate(-11deg);
            -ms-transform:     rotate(-11deg);
            -o-transform:      rotate(-11deg);
            transform:         rotate(-11deg);
            white-space: nowrap;
            position: absolute;
            left: 10%;
            top: 20px;
        }
        .broken h1 {
            font-size: 4em;
        }
        .how-dare-you-break-me {
            -webkit-transform: rotate(9deg);
            -moz-transform:    rotate(9deg);
            -ms-transform:     rotate(9deg);
            -o-transform:      rotate(9deg);
            transform:         rotate(9deg);
            white-space: nowrap;
            margin-left: 40%;
            margin-top: 40px;
            margin-bottom: 40px;
        }

        .teapot {
            margin-top: -15px;
            font-size: 0.7em;
            color: #c1c1c1;
        }

        .status {
            padding-top: 90px;
        }
    </style>
    <div class="row open-sans error-page">
        <div class="col-lg-12">
            <div class="text-muted broken">
                <h1>i'm broken :-(</h1>
                <p class="teapot text-muted">418? no, definitely not... wait, i know it!</p>
            </div>
            <h2 class="status" >{{$response['status']}}</h2>
            <h3>(that means <strong>{{strtolower($response['statusName'])}}</strong>)</h3>
            <br/>
            @if ($response['message'])
                <p class="text-muted">i guess what i'm really trying to say here is this:</p>
                <h3 style="font-size: 2.7em;">{!! trim(nl2br(e($response['message'])), '.') !!}</h3>
            @endif
            @if (isset($response['flattenException']) && $response['flattenException'])
            <?php
                function formatClass($class)
                {
                    $parts = explode('\\', $class);

                    return sprintf('<abbr title="%s">%s</abbr>', e($class), array_pop($parts));
                }
                function formatPath($path, $line)
                {
                    $path = e($path);
                    $file = preg_match('#[^/\\\\]*$#', $path, $file) ? $file[0] : $path;

                    //if ($linkFormat = $this->fileLinkFormat) {
                    //    $link = strtr($this->escapeHtml($linkFormat), array('%f' => $path, '%l' => (int) $line));
                    //
                    //    return sprintf(' in <a href="%s" title="Go to source">%s line %d</a>', $link, $file, $line);
                    //}

                    return sprintf(' in <a title="%s line %3$d" ondblclick="var f=this.innerHTML;this.innerHTML=this.title;this.title=f;">%s:%d</a>', $path, $file, $line);
                }
                function formatArgs(array $args)
                {
                    $result = array();
                    foreach ($args as $key => $item) {
                        if ('object' === $item[0]) {
                            $formattedValue = sprintf('<em class="text-muted">object</em>(%s)', formatClass($item[1]));
                        } elseif ('array' === $item[0]) {
                            $formattedValue = sprintf('<em class="text-muted">array</em>(%s)', is_array($item[1]) ? formatArgs($item[1]) : $item[1]);
                        } elseif ('string' === $item[0]) {
                            $formattedValue = sprintf("'%s'", e($item[1]));
                        } elseif ('null' === $item[0]) {
                            $formattedValue = '<em>null</em>';
                        } elseif ('boolean' === $item[0]) {
                            $formattedValue = '<em>'.strtolower(var_export($item[1], true)).'</em>';
                        } elseif ('resource' === $item[0]) {
                            $formattedValue = '<em>resource</em>';
                        } else {
                            $formattedValue = str_replace("\n", '', var_export(e((string) $item[1]), true));
                        }

                        $result[] = is_int($key) ? $formattedValue : sprintf("'%s' => %s", $key, $formattedValue);
                    }

                    return implode(', ', $result);
                }

                $flattenException = $response['flattenException'];
                $count = count($flattenException->getAllPrevious());
                $total = $count + 1;
            ?>
                @foreach ($flattenException->toArray() as $position => $e)
                <?php
                    $ind = $count - $position + 1;
                    $path = formatPath($e['trace'][0]['file'], $e['trace'][0]['line']);
                ?>
                    <div class="panel panel-default exception">
                        <div class="panel-heading">
                            <h3 class="panel-title">
                                {{$ind}}/{{$total}}: {!! formatClass($e['class']) !!} {!! $path !!}
                            </h3>
                            <small>{{$e['message']}}</small>
                        </div>
                        <div class="panel-body">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Function</th>
                                        <th>Location</th>
                                    </th>
                                </thead>
                                <tbody>
                                    <?php $i = 0?>
                                    @foreach ($e['trace'] as $trace)
                                    <tr>
                                        <td>{{$i++}}.</td>
                                        @if ($trace['function'])
                                            <td>{!! formatClass($trace['class']) !!}{{$trace['type']}}{{$trace['function']}}({!! formatArgs($trace['args']) !!})</td>
                                        @else
                                            <td></td>
                                        @endif

                                        @if(isset($trace['file']) && isset($trace['line']))
                                            <td>{!! formatPath($trace['file'], $trace['line']) !!}</td>
                                        @else
                                            <td></td>
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @endif
            <br/>
            <div class="how-dare-you-break-me">
                <p class="text-muted">was it you? i bet it was you!</p>
                <p class="text-muted">how could there be an error with me? i'm flawless!</p>
            </div>
            <br/>
        </div>
    </div>
@stop

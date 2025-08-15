@extends('admin._layoutNew')

@section('page-head')

@endsection

@section('page-content')
    <form class="layui-form" action="" style="width:500px;margin:0px auto">
        <img src="{{$qrCodeUrl}}">
        <div>
            <div>安全码:</div>
            {{$secret}}
            <input type="hidden" name="secret" value="{{$secret}}" />
        </div>
        <div>
            <div>谷歌验证码:</div>
            <input style="width:300px" type="text" class="layui-input" name="code" value="" />
        </div>
        
        <div class="layui-form-item" style="margin-top:10px">
            <div class="layui-input-block">
                <button class="layui-btn" lay-submit="" lay-filter="demo1">立即提交</button>
            </div>
        </div>
    </form>

@endsection

@section('scripts')
    <script>
       

        layui.use(['form','laydate'],function () {
            var form = layui.form
                ,$ = layui.jquery
                ,laydate = layui.laydate
                ,index = parent.layer.getFrameIndex(window.name);
            //监听提交
            form.on('submit(demo1)', function(data){
                var data = data.field;
                $.ajax({
                    url:'{{url('admin/user/set_info')}}'
                    ,type:'post'
                    ,dataType:'json'
                    ,data : data
                    ,success:function(res){
                        if(res.type=='error'){
                            layer.msg(res.message);
                        }else{
                            parent.layer.close(index);
                            parent.window.location.reload();
                        }
                    }
                });
                return false;
            });
        });
    </script>

@endsection
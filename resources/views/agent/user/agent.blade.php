@extends('agent.layadmin')

@section('page-head')

@endsection
@section('page-content')
    <form class="layui-form" action="">
        <div class="layui-form-item">
            <label class="layui-form-label">用户账号</label>
            <div class="layui-input-block">
                <input type="text" name="account_number" autocomplete="off" placeholder="" class="layui-input" value="{{$result->account_number}}" disabled>
            </div>
        </div>
        

        <div class="layui-form-item">
            <label class="layui-form-label">代理商</label>
            <div class="layui-input-block">
                <select name="risk" lay-verify="required" lay-filter="risk_mode">
                    <option value=""></option>
                    <!--<option value="0" {{ ($result->agent_note_id ?? 0) == 0 ? 'selected' : '' }} >无</option>-->
                    @foreach($list as $k => $v)
                        <option value="{{@$v->user_id}}" {{ ($result->agent_note_id ?? 0) == $v->user_id ? 'selected' : '' }} >{{@$v->user_id}}</option>
                    @endforeach
                    <!--<option value="0" {{ ($result->risk ?? 0) == 0 ? 'selected' : '' }} >无</option>-->
                    <!--<option value="1" {{ ($result->risk ?? 1) == 1 ? 'selected' : '' }} >盈利</option>-->
                    <!--<option value="-1" {{ ($result->risk ?? 2) == -1 ? 'selected' : '' }} >亏损</option>-->
                </select>
            </div>
        </div>

        <input type="hidden" name="id" value="{{$result->id}}">
        <div class="layui-form-item">
            <div class="layui-input-block">
                <button class="layui-btn" lay-submit="" lay-filter="demo1">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
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
                    url:'{{url('/agent/user/agent')}}'
                    ,type:'post'
                    ,dataType:'json'
                    ,data : data
                    ,success:function(res){
                        console.log(res);
                        if(res.code==1){
                            layer.msg(res.msg);
                        }else{
                            layer.msg(res.msg);
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


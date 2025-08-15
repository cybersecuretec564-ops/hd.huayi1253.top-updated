@extends('admin._layoutNew')

@section('page-head')

@endsection

@section('page-content')

<div class="layui-form">
    <div class="layui-form-item">
        <label class="layui-form-label">预设盈利状态</label>
        <div class="layui-input-block">
            <select name="risk" lay-verify="required" lay-filter="risk_mode">
                <option value=""></option>
                <option value="0" {{ (Request::get('result') ?? 0) == 0 ? 'selected' : '' }} >无</option>
                <option value="1" {{ (Request::get('result') ?? 0) == 1 ? 'selected' : '' }} >盈利</option>
                <option value="-1" {{ (Request::get('result') ?? 0) == -1 ? 'selected' : '' }} >亏损</option>
            </select>
        </div>
    </div>
    <div class="layui-form-item">
        <label for="price" class="layui-form-label">涨幅(%)</label>
        <div class="layui-input-block">
            <input type="text" class="layui-input" name="zf" placeholder="请输入涨幅" value="{{Request::get('zf')}}" >
        </div>
    </div>
    <div class="layui-form-item">
        <label for="price" class="layui-form-label">时间(分钟)</label>
        <div class="layui-input-block">
            <input type="text" class="layui-input" name="timer" placeholder="请输入时间(分钟)" value="{{Request::get('timer')}}" >
        </div>
    </div>
    <input type="hidden" name="id" value="{{Request::get('id')}}">
    <div class="layui-form-item">
        <div class="layui-input-block">
            <button class="layui-btn" lay-submit lay-filter="form">确定</button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    layui.use(['form', 'layer'], function () {
        var form = layui.form
            ,layer = layui.layer
            ,$ = layui.$
        form.on('submit(form)', function (data) {
            console.log(data);
            if(data.field.risk != "0"){
                if(Number(data.field.zf) <= 0){
                    layer.msg('请填写正确的涨幅');
                    return false;
                }
                if(Number(data.field.timer) <= 0){
                    layer.msg('请填写正确的时间');
                    return false;
                }
            }
            layer.confirm('确认要设置吗?', {
                title: '操作确认'
            }, function (index) {
                $.ajax({
                    url: '{{url('/admin/hazard/handle_new_post')}}',
                    type: 'POST',
                    data: data.field,
                    success: function (res) {
                        layer.msg(res.message, {
                            time: 2000
                            ,end: function () {
                                if (res.type == 'ok') {
                                    var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
                                    parent.layer.close(index); //再执行关闭
                                    parent.layui.table.reload('data_table');
                                }
                            }
                        });
                    },
                    error: function (res) {
                        layer.msg('网络错误,请稍后重试');
                    }
                });
            });
            
        });
    });
</script>
@endsection
@extends('admin._layoutNew')

@section('page-head')

@endsection

@section('page-content')

<div class="layui-form">
    <div class="layui-form-item">
        <div>请输入正负调整值</div>
        <input type="text" class="layui-input" onblur="change_num()" οnchange="change_num()" name="change" placeholder="请输入生效趋势" value="0" >
        <div style="margin-top:10px">
            <button class="layui-btn" onclick="jia()">加0.00001</button>
            <button class="layui-btn" onclick="jian()">减0.00001</button>
        </div>
    </div>
    
    <div class="layui-form-item">
        <div>生效趋势(秒,0为立即生效)</div>
        <input type="text" class="layui-input" name="result" placeholder="请输入生效趋势" value="0" >
    </div>
    
    <div class="layui-form-item">
        <div>调整值</div>
        <div style="display: flex;justify-content: space-between;background: #596F8E;color: white;">
            <div style="width:25%;border: 1px solid #ddd;text-align: center;">官方币价</div>
            <div style="width:25%;border: 1px solid #ddd;text-align: center;">原值</div>
            <div style="width:25%;border: 1px solid #ddd;text-align: center;">调整后</div>
            <div style="width:25%;text-align: center;border: 1px solid #ddd;">累计修正值</div>
        </div>
        <div style="display: flex;justify-content: space-between;background: #ECF1F8;">
            <div style="width:25%;border: 1px solid #ddd;text-align: center;">
                {{$last_price -> sj}}
            </div>
            <div style="width:25%;border: 1px solid #ddd;text-align: center;" id="old_number">
                {{$last_price -> now_price}}
            </div>
            <div style="width:25%;border: 1px solid #ddd;text-align: center;">
                <span  id="new_number" style="background: #ef4836;padding: 1px 6px;color:white;font-weight: 600;border-radius: 4px;">{{$result_now_price}}</span>
            </div>
            <div style="width:25%;text-align: center;border: 1px solid #ddd;"  id="number">
                {{$total_fix}}
            </div>
        </div>
    </div>
    
    <div class="layui-form-item">
        <div>生效趋势</div>
        <div style="display: flex;justify-content: space-between;background: #596F8E;color: white;">
            <div style="width:50%;text-align: center;">待生效值</div>
            <div style="width:50%;text-align: center;">时间(秒)</div>
        </div>
        <div style="display: flex;justify-content: space-between;background: #ECF1F8;">
            @if ($flag)
            <div style="width:50%;border: 1px solid #ddd;text-align: center;">
                {{$w_total_fix}}
            </div>
            <div style="width:50%;text-align: center;border: 1px solid #ddd;">
                {{$sym}}
            </div>
            @endif 
            
        </div>
    </div>
    <input type="hidden" id="id" name="id" value="{{Request::get('id')}}">
    <input type="hidden" id="fix" value="{{$total_fix}}" >
    <div class="layui-form-item" style="display:flex;justify-content:end">
        <button class="layui-btn" lay-submit lay-filter="form" style="width:50%">确定</button>
    </div>
</div>

@endsection

@section('scripts')
<script>
        function jia(){
            var change = $("input[name='change']").val();
            if(!change){
                change = 0;
            }
            change = parseFloat(parseFloat(change) + parseFloat(0.00001)).toFixed(5);
            $("input[name='change']").val(change);
            change_num();
        }
        function jian(){
            var change = $("input[name='change']").val();
            if(!change){
                change = 0;
            }
            change = parseFloat(parseFloat(change) - parseFloat(0.00001)).toFixed(5);
            $("input[name='change']").val(change);
            change_num();
        }
        function change_num(){
            var change = $("input[name='change']").val();
            $.ajax({
                url: '{{url('/admin/hazard/getNowPrices')}}',
                type: 'GET',
                data: {id:$("#id").val()},
                async:false,
                success: function (res) {
                    $("#old_number").html(res.message);
                    var number = $.trim($("#number").html());
                    if(!number){
                        number = 0;
                    }
                    var fix = $.trim($("#fix").val());
                    
                    var old_number = $.trim($("#old_number").html());
                    var new_number_result = parseFloat(parseFloat(change) + parseFloat(number) + parseFloat(old_number)).toFixed(5);
                    $("#new_number").html(new_number_result);
                    
                    var number_result = parseFloat(parseFloat(change) + parseFloat(fix)).toFixed(5);
                    $("#number").html(number_result);
                },
                error: function (res) {
                    layer.msg('网络错误,请稍后重试');
                }
            });
        }
    layui.use(['form', 'layer'], function () {
        var form = layui.form
            ,layer = layui.layer
            ,$ = layui.$
        
        form.on('submit(form)', function (data) {
            layer.confirm('确认要设置吗?', {
                title: '操作确认'
            }, function (index) {
                $.ajax({
                    url: '{{url('/admin/hazard/handle_kline')}}',
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
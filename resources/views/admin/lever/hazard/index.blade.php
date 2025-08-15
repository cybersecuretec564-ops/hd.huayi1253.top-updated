@extends('admin._layoutNew')

@section('page-head')
<style>
    .number {
        text-align: right;
        margin-right: 10px;
    }
    .layui-form-label {
        width: unset;
    }
</style>
@endsection

@section('page-content')
<div class="layui-form">
    <div class="layui-form-item">
        <input name="user_id" type="hidden" value="{{Request::get('user_id')}}">
        <div class="layui-inline">
            <label class="layui-form-label">法币</label>
            <div class="layui-input-inline" style="width:90px;">
                <select name="legal_id" lay-verify="required">
                    <option value="-1">无</option>
                    @foreach ($currencies as $key => $currency)
                        <option value="{{$currency->id}}">{{$currency->name}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="layui-inline">
            <label class="layui-form-label">方向</label>
            <div class="layui-input-inline" style="width:120px;">
                <select name="type" lay-verify="required">
                    <option value="-1">全部</option>
                    <option value="1">买入(做多)</option>
                    <option value="2">卖出(做空)</option>
                </select>
            </div>
        </div>
        <!--
        <div class="layui-inline">
            <label class="layui-form-label">风险率</label>
            <div class="layui-input-inline" style="width:90px; margin-right: 0px">
                <select name="operate" lay-verify="required">
                    <option value="-1">范围</option>
                    <option value="1">&gt;=</option>
                    <option value="2">&lt;=</option>
                </select>
            </div>
            <div class="layui-input-inline" style="width:80px; margin-right: 0px">
                <input type="text" class="layui-input" name="hazard_rate" placeholder="输入数值">
            </div>
            <div class="layui-form-mid layui-word-aux">%</div>
        </div>
        -->
        <div class="layui-inline">
            <button class="layui-btn" lay-submit lay-filter="submit">查询</button>
        </div>
    </div>
    
    <div>
        <div class="layui-inline">
            <label class="layui-form-label">币种</label>
            <div class="layui-input-inline" style="width:120px;">
                <select name="legal_id_dialog" id="legal_id_dialog" lay-verify="required">
                    @foreach ($matchs as $key => $currency)
                        <option value="{{$currency -> id}}">{{$currency -> currency_name}} / {{$currency -> legal_name}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="layui-inline">
            <button class="layui-btn" lay-submit lay-filter="submit1" id="klineChange">调整</button>
        </div>
    </div>
</div>
<table id="data_table" lay-filter="data_table"></table>
@endsection
@section('scripts')
<script type="text/html" id="addsonTpl">

        @{{#if(d.status == 0) { }}
        挂单中
        @{{#} else if(d.status == 1) { }}
        交易中
        @{{#} else if(d.status == 2) { }}
        平仓中
        @{{#} else if(d.status == 3) { }}
        已平仓
        @{{#} else if(d.status == 4) { }}
        已撤单

        @{{#}}}

    </script>
<script id="operate_bar" type="text/html">
    @{{#if(d.target_profit_price > 0 && (d.status == 0 || d.status == 1)) { }}
        <button class="layui-btn layui-btn-danger layui-btn-xs" lay-event="handle2">止盈平仓</button>
    @{{#}}}
    @{{#if(d.stop_loss_price > 0 && (d.status == 0 || d.status == 1)) { }}
        <button class="layui-btn layui-btn-danger layui-btn-xs" lay-event="handle3">止损平仓</button>
    @{{#}}}
    @{{#if(d.status == 0) { }}
        <button class="layui-btn layui-btn-danger layui-btn-xs" lay-event="handle5">强制撤销</button>
    @{{#} else if(d.status == 1) { }}
        <button class="layui-btn layui-btn-danger layui-btn-xs" lay-event="handle">强制平仓</button>
    @{{#}}}
</script>
<script>
    layui.use(['table', 'layer', 'form'], function() {
        
        var table = layui.table
            ,layer = layui.layer
            ,form = layui.form
            ,$ = layui.$
        var user_id = $('input[name=user_id]').val();
        var data_table = table.render({
            elem: '#data_table'
            ,url: '/admin/hazard/lists'
            ,height: 'full'
            ,toolbar: true
            ,page: true
            ,totalRow: true
            ,cols: [[
                {field: 'id', title: 'id', width: 80, totalRowText: '小计:'}
                ,{field: 'symbol', title: '交易对', width: 110}
                ,{field: 'type_name', title: '方式', width: 80}
                ,{field: 'account_number', title: '交易账号', width: 130}
                ,{field: 'price', title: '开仓价格', width: 130, templet: '<div><p class="number"><span>@{{ Number(d.price).toFixed(6) }}</span></p></div>'}
                ,{field: 'update_price', title: '当前价格', width: 130, templet: '<div><p class="number"><span>@{{ Number(d.update_price).toFixed(6) }}</span></p></div>'}
                ,{field: 'target_profit_price', title: '止盈价格', width: 130, templet: '<div><p class="number"><span>@{{ Number(d.target_profit_price).toFixed(6) }}</span></p></div>'}
                ,{field: 'stop_loss_price', title: '止损价格', width: 130, templet: '<div><p class="number"><span>@{{ Number(d.stop_loss_price).toFixed(6) }}</span></p></div>'}
                ,{field: 'share', title: '手数', width: 90, hide: true}
                ,{field: 'multiple', title: '倍数', width: 90}
                ,{field: 'number', title: '数量', width: 90}
                ,{field: 'status', title: '当前状态', sort: true, width: 170, templet: '#addsonTpl'}
                ,{field: 'caution_money', title: '保证金', width: 130, totalRow: true}
                ,{field: 'profits', title: '盈亏', width: 150, sort: true, totalRow: true, templet: '<div><p class=""><span>@{{ Number(d.profits).toFixed(4) }}</span></p></div>'}
                ,{fixed: 'right', title: '操作', toolbar: '#operate_bar',width:300}
                //,{field: 'profits_total', title: '盈亏总额', width: 150, sort: true, totalRow: true, templet: '<div><p class=""><span>@{{ Number(d.profits_total).toFixed(4) }}</span></p></div>'}
                //,{field: 'caution_money_total', title: '保证金总额', width: 150, sort: true, totalRow: true, templet: '<div><p class=""><span>@{{ Number(d.caution_money_total).toFixed(4) }}</span></p></div>'}
                //,{field: 'hazard_rate', title: '风险率', width: 150, sort: true, templet: '<div><p class="number"><span>@{{ d.hazard_rate }}</span><span>%</span></p></div>'}
                //,{field: '', title: '爆仓价', width: 120}
                //,{fixed: 'right', title: '操作', width: 120}
            ]]
            ,where : {
                user_id: user_id
            }
        });
        table.on('tool(data_table)', function (obj) {
            var layEvent = obj.event
                ,data = obj.data
            if (layEvent == 'handle2') {
                layer.confirm('确认要止盈平仓吗?', {
                    title: '操作确认'
                }, function (index) {
                    $.ajax({
                        url:"{{url('/admin/hazard/handle_y_new')}}",
                        type: 'POST',
                        data: {id:data.id},
                        success: function (res) {
                            layer.msg(res.message);
                            parent.layer.close(index); //再执行关闭
                            data_table.reload();
                        },
                        error: function (res) {
                            layer.msg('网络错误,请稍后重试');
                        }
                    });
                })
            }
            if (layEvent == 'handle5') {
                layer.confirm('确认要强制撤销吗?', {
                    title: '操作确认'
                }, function (index) {
                    $.ajax({
                        url:"{{url('/admin/hazard/handle_new1')}}",
                        type: 'POST',
                        data: {id:data.id},
                        success: function (res) {
                            layer.msg(res.message);
                            parent.layer.close(index); //再执行关闭
                            data_table.reload();
                        },
                        error: function (res) {
                            layer.msg('网络错误,请稍后重试');
                        }
                    });
                })
            }
            if (layEvent == 'handle') {
                layer.confirm('确认要强制平仓吗?', {
                    title: '操作确认'
                }, function (index) {
                    $.ajax({
                        url:"{{url('/admin/hazard/handle_new')}}",
                        type: 'POST',
                        data: {id:data.id},
                        success: function (res) {
                            layer.msg(res.message);
                            parent.layer.close(index); //再执行关闭
                            data_table.reload();
                        },
                        error: function (res) {
                            layer.msg('网络错误,请稍后重试');
                        }
                    });
                })
            }
            if (layEvent == 'handle3') {
                layer.confirm('确认要止损平仓吗?', {
                    title: '操作确认'
                }, function (index) {
                    $.ajax({
                        url:"{{url('/admin/hazard/handle_c_new')}}",
                        type: 'POST',
                        data: {id:data.id},
                        success: function (res) {
                            layer.msg(res.message);
                            parent.layer.close(index); //再执行关闭
                            data_table.reload();
                        },
                        error: function (res) {
                            layer.msg('网络错误,请稍后重试');
                        }
                    });
                })
            }
            if (layEvent == 'handle1') {
                layer.open({
                    type: 2
                    ,title: '交易处理'
                    ,content: '/admin/hazard/handle' + '?id=' + data.id  + '&zf=' + data.zf + '&timer=' + data.timer + '&result=' + data.result
                    ,area: ['580px', '320px']
                });
            }
        });
        form.on('submit(submit)', function (data) {
            var option = {
                where: data.field
            }
            data_table.reload(option);
        });
        
        $("#klineChange").click(function(){
            var id = $("#legal_id_dialog option:selected").val();
            var text = $("#legal_id_dialog option:selected").text();
            
            layer.open({
                type: 2
                ,title: '调整'+ text
                ,content: '/admin/hazard/handle_kline' + '?id=' + id
                ,area: ['580px', '520px']
            });
        });
    });
</script>
@endsection
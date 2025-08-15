@extends('admin._layoutNew')

@section('page-head')
    <style type="text/css">
        
    </style>
@endsection

@section('page-content')
    <div class="layui-inline">
        <button class="layui-btn layui-btn-normal layui-btn-radius" id="add_mail_message">添加机构信息</button>
       <!--<div class="layui-input-inline date_time111" style="margin-left: 50px;">-->
       <!--    <input type="text" name="start_time" id="start_time" placeholder="请输入开始时间" autocomplete="off" class="layui-input" value="">-->
       <!--</div>-->
       <!--<div class="layui-input-inline date_time111" style="margin-left: 50px;">-->
       <!--    <input type="text" name="end_time" id="end_time" placeholder="请输入结束时间" autocomplete="off" class="layui-input" value="">-->
       <!--</div>-->
        <button class="layui-btn btn-search" id="mobile_search" lay-submit lay-filter="mobile_search"> <i class="layui-icon">&#xe615;</i> </button>
    </div>
    
    <table class="layui-hide" id="mailMessageList" lay-filter="mailMessageList"></table>
    
    <script type="text/html" id="barDemo">
       <a class="layui-btn layui-btn-xs" lay-event="edit">修改</a>
       <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del">删除</a>
    </script>
@endsection

@section('scripts')

    <script>

                window.onload = function() {
                    document.onkeydown=function(event){
                        var e = event || window.event || arguments.callee.caller.arguments[0];
                        if(e && e.keyCode==13){ // enter 键
                            $('#mobile_search').click();
                        }
                    };
                    layui.use(['element', 'form', 'layer', 'table','laydate'], function () {
                        var element = layui.element;
                        var layer = layui.layer;
                        var table = layui.table;
                        var $ = layui.$;
                        var form = layui.form;
                        var laydate = layui.laydate;
                        $('#add_mail_message').click(function(){layer_show('添加机构信息', '/admin/loan/mechanism_add');});
                        laydate.render({
                            elem: '#start_time'
                        });
                        laydate.render({
                            elem: '#end_time'
                        });

                        form.on('submit(mobile_search)',function(obj){
                            var start_time =  $("#start_time").val()
                            var end_time =  $("#end_time").val()
                            var user_id =  $("#user_id").val()
                            tbRend("{{url('/admin/loan/mechanism_list')}}?start_time="+start_time
                                +'&end_time='+end_time
                            );
                            return false;
                        });
                        function tbRend(url) {
                            table.render({
                                elem: '#mailMessageList'
                                ,url: url
                                ,page: true
                                ,limit: 20
                                ,height: 'full-100'
                                ,toolbar: true
                                ,cols: [[
                                    {field: 'id', title: 'ID'}
                                    ,{field:'day',title: '天数'}
                                    ,{field:'rate',title: '利率(%)'}
                                    ,{field:'name',title: '机构名称'}
                                    ,{field:'currency_code',title: '币种'}
                                    ,{field:'min',title: '最小金额'}
                                    ,{field:'max',title: '最大金额'}
                                    ,{fixed: 'right', title: '操作', minWidth: 150, align: 'center', toolbar: '#barDemo'}
                                ]]
                            });
                        }
                        tbRend("{{url('/admin/loan/mechanism_list')}}");
                        //监听工具条
                        table.on('tool(mailMessageList)', function (obj) { //注：tool是工具条事件名，test是table原始容器的属性 lay-filter="对应的值"
                            var data = obj.data;
                            var layEvent = obj.event;
                            var tr = obj.tr;
                            
                            if(layEvent === 'del'){ //删除
                                layer.confirm('真的要删除吗？', function(index){
                                    //向服务端发送删除指令
                                    $.ajax({
                                        url:'/admin/loan/mechanism_del',
                                        type:'post',
                                        dataType:'json',
                                        data:{id:data.id},
                                        success:function(res){
                                            if(res.type=='ok'){
                                                obj.del(); //删除对应行（tr）的DOM结构，并更新缓存
                                                layer.msg(res.message);
                                                layer.close(index);
                                            }else{
                                                layer.close(index);
                                                layer.alert(res.message);
                                            }
                                        }
                                    });
                                });
                            }
                    
                            if (layEvent === 'edit') { //编辑
                                layer_show('修改机构信息', '/admin/loan/mechanism_add?id=' + data.id);
                            }
                            
                        });
                    });
                }
            </script>    
@endsection
import websocket
import threading
import gzip
import json
import time
# import _thread
import logging
import redis

# 本地线程数据存储
local_data = threading.local()

# Redis 连接
r = redis.StrictRedis(host='127.0.0.1', port=6379 ,password='EG1ES51Ege')

# 设置日志
logging.basicConfig(level=logging.WARNING,
                    filename='/tmp/python.log',
                    filemode='a',
                    format='%(asctime)s - %(message)s')

PERIODS = ['1min', '5min', '15min', '30min', '60min', '1day', '1week', '1mon', 'depth', 'detail']
CURRENCIES = ['btc', 'eth', 'xrp', 'ltc', 'eos', 'bch', 'etc', 'trb', 'iota', 'qtum', 'snt', 'wicc', 'neo', 'yee', 'doge']

def on_connect(ws):
    """
    WebSocket连接成功时触发，发送订阅请求。
    """
    print(f"{local_data.work_id} 连接成功")
    # logging.info(f"{local_data.work_id} 连接成功")
    
	# 订阅周期和货币对
   
    for index, currency in enumerate(CURRENCIES):
        if local_data.work_id == 9:
            key = f"market.{currency}usdt.trade.detail"
        elif local_data.work_id == 8:
            key = f"market.{currency}usdt.depth.step0"
        else:
            key = f"market.{currency}usdt.kline.{PERIODS[local_data.work_id]}"
        
        # 发送订阅消息
        ws.send(json.dumps({"sub": key, "id": key}))
        time.sleep(0.2)

def on_error(ws, error):
    """
    WebSocket连接出错时触发，尝试重连。
    """
    print(f"{local_data.work_id} 出错: {error}")
    logging.error(f"{local_data.work_id} 出错: {error}，尝试重连")
    reconnect(ws)

def on_close(ws, close_status_code, close_msg):
    """
    WebSocket连接关闭时触发，尝试重连。
    """
    print(f"{local_data.work_id} 连接关闭")
    logging.error(f"{local_data.work_id} 连接关闭，状态码: {close_status_code}，消息: {close_msg}")
    reconnect(ws)

def on_message(ws, data):
    """
    WebSocket接收到消息时触发。
    """
    try:
        obj = gzip.decompress(data).decode()
        obj = json.loads(obj)

        if 'ping' in obj:
            print(f"{local_data.work_id} 收到心跳")
            ws.send(json.dumps({"pong": obj['ping']}))
            print(f"{local_data.work_id} 回应心跳")
        else:
            if 'ch' in obj:
                json_str = json.dumps(obj)
                json_str = json_str.replace("yee", "hkcc")
                r.set(obj['ch'].replace('yee', 'hkcc'), json_str)
    except Exception as e:
        print(f"处理消息出错: {e}")
        logging.error(f"处理消息出错: {e}")

def reconnect(ws):
    """
    WebSocket 连接断开后，重新连接
    """
    print(f"{local_data.work_id} 正在尝试重连...")
    logging.info(f"{local_data.work_id} 正在尝试重连...")
    time.sleep(5)  # 等待 5 秒后重连
    socket(local_data.work_id)

def socket(id):
    """
    启动 WebSocket 连接
    """
    local_data.work_id = id
    websocket.enableTrace(False)
    ws = websocket.WebSocketApp("wss://api.huobi.pro/ws",
                                on_message=on_message,
                                on_error=on_error,
                                on_close=on_close)
    local_data.ws = ws
    ws.on_open = on_connect
    ws.run_forever()

# 启动线程来处理 WebSocket 连接
def start_threads():
    threads = []
    for index, _ in enumerate(PERIODS):
        # 使用 threading.Thread 替代 _thread.start_new_thread
        thread = threading.Thread(target=socket, args=(index,))
        threads.append(thread)
        thread.start()

    # 等待所有线程完成
    for thread in threads:
        thread.join()

# 主程序入口
if __name__ == "__main__":
    start_threads()

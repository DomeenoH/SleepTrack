### 项目简介

这个项目是一个用于跟踪用户睡眠和清醒状态的系统，通过最近的状态数据计算睡眠质量和精神状态，并以JSON格式返回这些信息。系统主要由PHP后台程序和前端页面组成。
感谢[毛毛同学](https://github.com/WinMEMZqwq)在[B站视频](https://www.bilibili.com/video/BV1fE421A7PE/)中提供的灵感。  

 ![image](https://github.com/DomeenoH/SleepTrack/assets/29730075/c8f59fc5-9cee-49fb-8d8f-bd9cf60e5493)
#### 功能特色
1. **状态记录**：系统能够记录用户的睡眠和清醒状态，包括每次状态变化的时间戳。
2. **睡眠质量计算**：根据最近的状态数据，系统计算用户的睡眠时间、清醒时间以及总时间，从而得出睡眠质量。
3. **精神状态评估**：系统根据睡眠质量自动评估用户的精神状态，并返回相应的等级。
4. **数据更新**：通过POST和GET请求更新和获取最新的状态数据。如果新状态与之前的状态相同，则系统会提示不需要更新。
5. **动态前端展示**：前端页面能够动态展示当前状态及其持续时间、最近的状态变化以及睡眠情况分析。

#### 文件结构
- **`status.php`**：主要的后台处理文件，负责状态记录、睡眠质量计算和数据返回。
- **`login.php`**：登录页。
- **`logout.php`**：用于注销的后台处理文件。
- **`manage.php`**：用于对记录文件进行简单的修改。
- **`status_log.txt`**：用于存储状态变化日志的文件。
- **`index.html`**：用于展示状态数据的前端页面。


#### 使用方法

1. **POST请求**：
    - 用于更新用户的状态。
    - 请求参数：
      - `status`: 用户当前状态（"睡着" 或 "醒着"）
      - `key`: 验证密钥
    - 示例：
      ```bash
      curl -X POST -d "status=睡着&key=YOURSECRETKEY" http://yourdomain.com/status.php
      ```

2. **GET请求**：
    - 用于获取用户的当前状态和睡眠质量数据。
    - 请求参数：
      - `status`: 用户当前状态（"睡着" 或 "醒着"）
      - `key`: 验证密钥
    - 示例：
      ```bash
      curl -G -d "status=醒着&key=YOURSECRETKEY" http://yourdomain.com/status.php
      ```

#### 项目实现

该项目95%由ChatGPT实现，包括代码编写、功能设计和文档撰写。

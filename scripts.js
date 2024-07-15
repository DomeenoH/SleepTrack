function formatDuration(seconds) {
  const days = Math.floor(seconds / 86400);
  seconds %= 86400;
  const hours = Math.floor(seconds / 3600);
  seconds %= 3600;
  const minutes = Math.floor(seconds / 60);
  seconds %= 60;

  let result = '';
  if (days > 0) {
    result += `${days}天${hours}小时${minutes}分钟${seconds}秒`;
  } else if (hours > 0) {
    result += `${hours}小时${minutes}分钟${seconds}秒`;
  } else if (minutes > 0) {
    result += `${minutes}分钟${seconds}秒`;
  } else {
    result += `${seconds}秒`;
  }
  return result;
}

function fetchStatus() {
  fetch('status.php')
    .then(response => response.json())
    .then(data => {
      const statusElement = document.getElementById('currentStatus');
      const historyTableBody = document.getElementById('historyTableBody');
      const sleepPercentageElement = document.getElementById('sleepPercentage');
      const summaryElement = document.getElementById('summary');
      const durationElement = document.getElementById('statusDuration');
      const awakeTimeElement = document.getElementById('awakeTime');
      const mentalStateElement = document.getElementById('mentalState');
      const sleepTimeElement = document.getElementById('sleepTime');
      const mentalStateProgressBar = document.getElementById('mentalStateProgressBar');

      statusElement.innerText = data.status;
      statusElement.classList.remove('awake', 'asleep', 'unknown');
      if (data.status === '醒着') {
        statusElement.classList.add('awake');
      } else if (data.status === '睡着') {
        statusElement.classList.add('asleep');
      } else {
        statusElement.classList.add('unknown');
      }

      historyTableBody.innerHTML = '';
      data.history.reverse().forEach((entry, index, array) => {
        const row = document.createElement('tr');
        const statusCell = document.createElement('td');
        const durationCell = document.createElement('td');

        statusCell.innerText = entry.status;

        let durationText;
        if (index === 0) {
          const duration = Math.floor(Date.now() / 1000) - entry.time;
          durationText = formatDuration(duration);
        } else {
          const duration = array[index - 1].time - entry.time;
          durationText = formatDuration(duration);
        }

        durationCell.innerText = durationText;

        // 添加背景颜色判断
        if (entry.status === '睡着') {
          row.classList.add('asleep-row');
        }

        row.appendChild(statusCell);
        row.appendChild(durationCell);
        historyTableBody.appendChild(row);
      });

      const statusDuration = Math.floor(Date.now() / 1000) - data.status_time;
      durationElement.innerText = formatDuration(statusDuration);

      awakeTimeElement.innerText = formatDuration(data.awake_time);
      sleepTimeElement.innerText = formatDuration(data.sleep_time);

      const sleepPercentage = data.sleep_quality.toFixed(2);
      sleepPercentageElement.innerText = `${sleepPercentage}%`;

        summaryElement.classList.remove('good', 'ok', 'bad');
        
        if (sleepPercentage >= 95 || sleepPercentage < 10) {
          summaryElement.classList.add('bad');
        } else if (sleepPercentage >= 70 || sleepPercentage < 30) {
          summaryElement.classList.add('bad');
        } else if (sleepPercentage >= 60) {
          summaryElement.classList.add('ok');
        } else if (sleepPercentage >= 45) {
          summaryElement.classList.add('good');
        } else if (sleepPercentage >= 30) {
          summaryElement.classList.add('ok');
        } else if (sleepPercentage >= 10) {
          summaryElement.classList.add('bad');
        } else {
          summaryElement.classList.add('bad');
        }
        
            



      const mentalState = data.mental_state;
      const mentalStateStyles = {
        '猝死边缘': 'black-background',
        '熬大夜': 'red-background',
        '没睡够': 'yellow-background',
        '很健康': 'green-background',
        '睡饱饱': 'yellow-background',
        '猪儿虫': 'red-background',
        '睡死了': 'black-background'
      };

      const styleClass = mentalStateStyles[mentalState] || '';
      mentalStateElement.classList.forEach(className => {
        if (Object.values(mentalStateStyles).includes(className)) {
          mentalStateElement.classList.remove(className);
        }
      });
      if (styleClass) {
        mentalStateElement.classList.add(styleClass);
      } else {
        mentalStateElement.style.backgroundColor = 'gray';
        mentalStateElement.style.color = 'white';
      }

      mentalStateElement.textContent = mentalState;

      const mentalStatePositions = {
        '猝死边缘': 0,
        '熬大夜': 10,
        '没睡够': 30,
        '很健康': 45,
        '睡饱饱': 60,
        '猪儿虫': 70,
        '睡死了': 95
      };
      const position = mentalStatePositions[data.mental_state];
      mentalStateProgressBar.style.left = `${position}%`;
    })
    .catch(error => console.error('Error fetching status:', error));
}

document.addEventListener("DOMContentLoaded", function() {
      fetchStatus();
  setInterval(fetchStatus, 1000);
  fetch('/status.php')
    .then(response => response.json())
    .then(data => {
      const currentStatusElement = document.getElementById('currentStatus');
      const statusDurationElement = document.getElementById('statusDuration');
      const historyTableBody = document.getElementById('historyTableBody');
      const sleepPercentageElement = document.getElementById('sleepPercentage');
      const awakeTimeElement = document.getElementById('awakeTime');
      const sleepTimeElement = document.getElementById('sleepTime');
      const progressTextElement = document.getElementById('progressText');
      const progressBarElement = document.getElementById('mentalStateProgressBar');

      // 设置进度条和文字信息
      const sleepQuality = data.sleep_quality;
      progressBarElement.style.width = `${sleepQuality+1}%`;
      progressTextElement.textContent = `${sleepQuality}% - ${data.mental_state}`;
      progressTextElement.style.color = 'white';

      // 根据数值设置进度条颜色
      if (sleepQuality >= 95) {
        progressBarElement.style.backgroundColor = 'black';
      } else if (sleepQuality >= 70) {
        progressBarElement.style.backgroundColor = 'red';
      } else if (sleepQuality >= 60) {
        progressBarElement.style.backgroundColor = 'yellow';
      } else if (sleepQuality >= 45) {
        progressBarElement.style.backgroundColor = 'green';
      } else if (sleepQuality >= 30) {
        progressBarElement.style.backgroundColor = 'yellow';
      } else if (sleepQuality >= 10) {
        progressBarElement.style.backgroundColor = 'red';
      } else {
        progressBarElement.style.backgroundColor = 'black';
      }
    })
    .catch(error => console.error('Error fetching status:', error));
});

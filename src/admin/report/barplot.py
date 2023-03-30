import matplotlib.pyplot as plt
import sys

filename = sys.argv[1]
title = sys.argv[2]


x = [str(x) + "-" + str(x + 10) for x in range(0, 300, 10)]

with open(filename, "r") as file:
    y = [int(line) for line in file]

# Create a bar plot
plt.bar(x, y, width=0.5)


# Set the y-axis tick labels to step by 5
plt.xticks(rotation=90)
if max(y) < 200:
    plt.yticks(range(0, max(y) + 1, 5))
else:
    plt.yticks(range(0, max(y) + 1, 10))
# Add gridlines to the y-axis
plt.grid(axis="y")

# Add a title to the plot
plt.title(title)

plt.tight_layout()

# Show the plot
plt.savefig(filename.replace(".txt", ".png"), dpi=600, transparent=True)
